<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class Ledger
{
    public static function today(): string
    {
        return today('Europe/Sofia')->toDateString();
    }

    /** Split signed amounts to the cent, assigning rounding remainders consistently. */
    public static function split(int $cents, array $shares): array
    {
        if ($shares === []) {
            return [];
        }
        ksort($shares);
        $weight = array_sum($shares);
        $absolute = abs($cents);
        $allocated = [];
        $remainders = [];

        foreach ($shares as $account => $share) {
            // Divide first so large totals cannot overflow when multiplied by the share.
            $fraction = ($absolute % $weight) * $share;
            $allocated[$account] = intdiv($absolute, $weight) * $share + intdiv($fraction, $weight);
            $remainders[$account] = $fraction % $weight;
        }

        arsort($remainders);
        $left = $absolute - array_sum($allocated);
        foreach (array_slice(array_keys($remainders), 0, $left) as $account) {
            $allocated[$account]++;
        }

        return $cents < 0 ? array_map(fn (int $amount) => -$amount, $allocated) : $allocated;
    }

    /** Receipt/expense totals use the selected dates. Balances include earlier unsettled amounts. */
    public function report(string $from, string $through, ?FinanceProject $only = null): array
    {
        $projects = $only ? collect([$only->id => $only]) : FinanceProject::orderBy('id')->get()->keyBy('id');
        $stats = [];
        $period = ['received' => 0, 'refunded' => 0, 'expenses' => 0, 'transfers' => 0];
        $companyReserve = 0;
        $receipts = [];
        $movements = FinanceMovement::posted()->where('occurred_on', '<=', $through)
            ->when($only, fn ($query) => $query->where(fn ($query) => $query
                ->where('finance_project_id', $only->id)->orWhere('kind', FinanceMovement::SETTLEMENT)))
            ->orderBy('occurred_on')
            ->orderByRaw('CASE WHEN kind = ? THEN 0 ELSE 1 END', [FinanceMovement::INCOME])
            ->orderBy('id')->cursor();

        foreach ($movements as $movement) {
            $amount = $movement->amount_cents;
            $inPeriod = $movement->occurred_on->toDateString() >= $from;

            if ($movement->kind === FinanceMovement::SETTLEMENT) {
                foreach ($movement->details['adjustments'] as $projectId => $adjustments) {
                    if ($projects->has($projectId)) {
                        $stats[$projectId] ??= self::emptyProject();
                        self::add($stats[$projectId]['held'], $adjustments);
                        $stats[$projectId]['settlements'][] = $movement->id;
                    }
                }
                if ($inPeriod && ! $only) {
                    $period['transfers'] += $amount;
                }

                continue;
            }

            $projectId = $movement->finance_project_id;
            $project = $projects->get($projectId);
            if (! $project) {
                continue;
            }

            $stats[$projectId] ??= self::emptyProject();
            $row = &$stats[$projectId];
            if ($movement->from_account_id) {
                self::add($row['held'], [$movement->from_account_id => -$amount]);
            }
            if ($movement->to_account_id) {
                self::add($row['held'], [$movement->to_account_id => $amount]);
            }

            $field = match ($movement->kind) {
                FinanceMovement::INCOME => 'received',
                FinanceMovement::REFUND => 'refunded',
                FinanceMovement::EXPENSE => 'expenses',
                FinanceMovement::TRANSFER => 'transfers',
            };
            $row[$field] += $amount;
            if ($inPeriod) {
                $period[$field] += $amount;
            }

            $reserve = 0;
            if ($movement->kind === FinanceMovement::INCOME) {
                $rate = (int) ($movement->details['company_reserve_bps'] ?? 0);
                $receipts[$projectId][$movement->id] = [$amount, $rate];
                $reserve = CompanyReserve::cents($amount, $rate);
            } elseif ($movement->kind === FinanceMovement::REFUND) {
                $receipts[$projectId] ??= [];
                $reserve = -CompanyReserve::release($amount, $receipts[$projectId]);
            }
            $row['company_reserve'] += $reserve;
            if ($inPeriod) {
                $companyReserve += $reserve;
            }

            if ($project->isSharedExpense() && $movement->kind === FinanceMovement::EXPENSE) {
                self::add($row['shares'], self::split(-$amount, $movement->details['shares']));
            }
            unset($row);
        }

        $balances = [];
        $unpaid = 0;
        $companyAccountId = FinanceAccount::companyId();
        $legacyCashId = FinanceAccount::companyAccounts()[FinanceAccount::LEGACY_CASH] ?? null;
        foreach ($projects as $project) {
            $stats[$project->id] ??= self::emptyProject();
            $row = &$stats[$project->id];
            $row['paid'] = $row['received'] - $row['refunded'];
            $row['net'] = $row['paid'] - $row['expenses'];
            $row['remaining'] = $project->agreed_cents !== null ? $project->agreed_cents - $row['paid'] : null;
            $unpaid += max(0, $row['remaining'] ?? 0);
            if ($legacyCashId && isset($row['held'][$legacyCashId])) {
                self::add($row['held'], [$companyAccountId => $row['held'][$legacyCashId]]);
                unset($row['held'][$legacyCashId]);
            }
            $row['distribution_pending'] = ! $project->isSharedExpense() && $project->shares === [];
            if (! $project->isSharedExpense()) {
                $row['shares'] = self::split($row['net'] - $row['company_reserve'], $project->shares);
            }

            if ($row['distribution_pending']) {
                $row['balance'] = self::pendingReserve($row['held'], $companyAccountId, $row['company_reserve']);
            } else {
                $row['balance'] = $row['held'];
                self::add($row['balance'], array_map(fn (int $value) => -$value, $row['shares']));
                self::add($row['balance'], [$companyAccountId => -$row['company_reserve']]);
            }
            $row['balance'] = array_filter($row['balance']);
            ksort($row['balance']);
            self::add($balances, $row['balance']);
            unset($row);
        }

        ksort($balances);

        return ['period' => $period, 'projects' => $stats, 'balances' => $balances, 'unpaid' => $unpaid, 'company_reserve' => $companyReserve];
    }

    private static function pendingReserve(array $held, int $company, int $reserve): array
    {
        $left = max(0, $reserve - ($held[$company] ?? 0));
        $balances = [];
        ksort($held);
        foreach ($held as $account => $cents) {
            if ($account !== $company && $cents > 0 && $left > 0) {
                $balances[$account] = min($left, $cents);
                $left -= $balances[$account];
            }
        }
        $balances[$company] = -array_sum($balances);

        return $balances;
    }

    private static function emptyProject(): array
    {
        return ['received' => 0, 'refunded' => 0, 'expenses' => 0, 'transfers' => 0, 'company_reserve' => 0, 'shares' => [], 'held' => [], 'settlements' => []];
    }

    private static function add(array &$target, array $values): void
    {
        foreach ($values as $account => $amount) {
            $target[$account] = ($target[$account] ?? 0) + $amount;
        }
    }

    /** Positive balance is money to give, negative balance is money to receive. */
    public static function transfers(array $balances): array
    {
        $owing = array_filter($balances, fn (int $amount) => $amount > 0);
        $owed = array_filter($balances, fn (int $amount) => $amount < 0);
        arsort($owing);
        asort($owed);
        $transfers = [];

        foreach ($owing as $payer => $available) {
            foreach ($owed as $payee => $amount) {
                if ($available === 0) {
                    break;
                }
                $pay = min($available, -$amount);
                if ($pay === 0) {
                    continue;
                }
                $transfers[] = ['from' => $payer, 'to' => $payee, 'cents' => $pay];
                $owed[$payee] += $pay;
                $available -= $pay;
            }
        }

        return $transfers;
    }

    /** @param FinanceProject|Closure(): FinanceProject $project */
    public function record(FinanceProject|Closure $project, array $input, User $actor, bool $fullUtilityShare = false): FinanceMovement
    {
        $data = validator($input, [
            'kind' => ['required', Rule::in([FinanceMovement::INCOME, FinanceMovement::EXPENSE, FinanceMovement::REFUND, FinanceMovement::TRANSFER])],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.self::today()],
            'amount' => ['required'],
            'payment_stage' => ['required_if:kind,income', 'nullable', Rule::in(array_keys(FinanceMovement::STAGES))],
            'from_account_id' => ['required_unless:kind,income', 'nullable', 'integer', 'exists:finance_accounts,id'],
            'to_account_id' => ['required_if:kind,income,transfer', 'nullable', 'integer', 'exists:finance_accounts,id', 'different:from_account_id'],
            'handled_by_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where('kind', 'member')],
            'method' => [$fullUtilityShare ? 'nullable' : 'required', Rule::in(array_keys(FinanceMovement::METHODS))],
            'description' => ['nullable', 'string', 'max:500'],
            'company_percentage' => ['nullable'],
            'company_deposit' => ['nullable', Rule::in(['pending', 'now'])],
        ])->validate();
        $data['from_account_id'] = FinanceAccount::canonicalId(isset($data['from_account_id']) ? (int) $data['from_account_id'] : null);
        $data['to_account_id'] = FinanceAccount::canonicalId(isset($data['to_account_id']) ? (int) $data['to_account_id'] : null);
        if ($data['from_account_id'] !== null && $data['from_account_id'] === $data['to_account_id']) {
            throw ValidationException::withMessages(['to_account_id' => 'Изберете различни сметки.']);
        }
        $amount = Euro::parse($data['amount']);
        $companyRate = $data['kind'] === FinanceMovement::INCOME ? CompanyReserve::basisPoints($data['company_percentage'] ?? null) : 0;

        return $this->transaction(function () use ($project, $data, $amount, $actor, $fullUtilityShare, $companyRate) {
            $project = $project instanceof Closure ? $project() : $project->refresh();
            $kind = $data['kind'];
            abort_if($fullUtilityShare && (! $project->isUtility() || $kind !== FinanceMovement::TRANSFER), 403);
            if ($project->isSharedExpense() && ! in_array($kind, [FinanceMovement::EXPENSE, FinanceMovement::TRANSFER], true)) {
                throw ValidationException::withMessages(['kind' => 'Клиентските плащания се записват по конкретен проект.']);
            }

            if ($project->isUtility() && $kind === FinanceMovement::EXPENSE && $project->utilityExpense()->exists()) {
                throw ValidationException::withMessages(['kind' => 'Тази битова сметка вече има запис на плащането. Можете да запишете възстановяване на сума.']);
            }

            if ($kind === FinanceMovement::REFUND && $amount > self::availableRefund($project, $data['occurred_on'])) {
                throw ValidationException::withMessages(['amount' => 'Сумата надвишава полученото след отчетените връщания от тази дата нататък.']);
            }

            if ($project->isUtility() && $kind === FinanceMovement::TRANSFER) {
                $this->validateBillRepayment($project, $data, $amount, $fullUtilityShare);
            }

            $details = $kind === FinanceMovement::INCOME ? ['company_reserve_bps' => $companyRate] : null;
            if ($project->isSharedExpense() && $kind === FinanceMovement::EXPENSE) {
                $shares = $project->shares ?: FinanceAccount::activeMembers()->pluck('id')->mapWithKeys(fn ($id) => [$id => 1])->all();
                if ($shares === []) {
                    throw ValidationException::withMessages(['kind' => 'Добавете участници в общите разходи, преди да запишете разход.']);
                }
                $details = ['shares' => $shares];
            }

            $movement = $project->movements()->create([
                'kind' => $kind,
                'payment_stage' => $kind === FinanceMovement::INCOME ? $data['payment_stage'] : null,
                'occurred_on' => $data['occurred_on'],
                'amount_cents' => $amount,
                'from_account_id' => $kind === FinanceMovement::INCOME ? null : $data['from_account_id'],
                'to_account_id' => in_array($kind, [FinanceMovement::INCOME, FinanceMovement::TRANSFER], true) ? $data['to_account_id'] : null,
                'handled_by_id' => $data['handled_by_id'] ?? null,
                'method' => $data['method'] ?? null,
                'description' => $data['description'] ?? null,
                'details' => $details,
                ...self::actorFields($actor),
            ]);

            if ($kind === FinanceMovement::INCOME && ($data['company_deposit'] ?? null) === 'now' && $data['to_account_id'] !== FinanceAccount::companyId()) {
                $reserve = CompanyReserve::cents($amount, $companyRate);
                if ($reserve > 0) {
                    $project->movements()->create([
                        'kind' => FinanceMovement::TRANSFER,
                        'occurred_on' => $data['occurred_on'],
                        'amount_cents' => $reserve,
                        'from_account_id' => $data['to_account_id'],
                        'to_account_id' => FinanceAccount::companyId(),
                        'description' => 'Фирмен дял от плащане №'.$movement->id,
                        'details' => ['company_receipt_id' => $movement->id],
                        ...self::actorFields($actor),
                    ]);
                }
            }

            return $movement;
        });
    }

    private static function availableRefund(FinanceProject $project, string $date): int
    {
        $days = $project->movements()->posted()->whereIn('kind', [FinanceMovement::INCOME, FinanceMovement::REFUND])
            ->select('occurred_on')->selectRaw('SUM(CASE WHEN kind = ? THEN amount_cents ELSE -amount_cents END) as net_cents', [FinanceMovement::INCOME])
            ->groupBy('occurred_on')->orderBy('occurred_on')->toBase()->cursor();
        $net = 0;
        $available = null;
        foreach ($days as $day) {
            if ($day->occurred_on > $date && $available === null) {
                $available = $net;
            }
            $net += (int) $day->net_cents;
            if ($day->occurred_on >= $date) {
                $available = min($available ?? $net, $net);
            }
        }

        return $available ?? $net;
    }

    private function validateBillRepayment(FinanceProject $project, array $data, int $amount, bool $fullShare): void
    {
        $date = $data['occurred_on'];
        $today = self::today();
        $limit = $amount;
        foreach (array_unique([$today, $date]) as $cutoff) {
            $balances = $this->report($cutoff, $cutoff, $project)['balances'];
            $limit = min($limit, $balances[$data['from_account_id']] ?? 0, -($balances[$data['to_account_id']] ?? 0));
            if ($fullShare && $cutoff === $today && ! in_array([
                'from' => (int) $data['from_account_id'], 'to' => (int) $data['to_account_id'], 'cents' => $amount,
            ], self::transfers($balances), true)) {
                throw ValidationException::withMessages(['amount' => 'Оставащият дял се промени. Проверете актуалното разпределение.']);
            }
        }

        if ($limit < $amount) {
            throw ValidationException::withMessages(['amount' => $limit > 0
                ? 'Между избраните сметки остава да се възстанови '.Euro::format($limit).'.'
                : 'По тази сметка вече няма дължима сума между избраните хора.']);
        }
    }

    public static function settlementPlan(array $report): array
    {
        $adjustments = [];
        foreach ($report['projects'] as $project => $row) {
            if ($row['balance'] !== []) {
                $adjustments[$project] = array_map(fn (int $amount) => -$amount, $row['balance']);
            }
        }

        return ['adjustments' => $adjustments, 'transfers' => self::transfers($report['balances'])];
    }

    public static function fingerprint(array $plan): string
    {
        return hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
    }

    public function settle(string $date, string $fingerprint, ?string $method, User $actor): FinanceMovement
    {
        validator(['date' => $date, 'method' => $method], [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.self::today()],
            'method' => ['nullable', Rule::in(array_keys(FinanceMovement::METHODS))],
        ])->validate();

        return $this->transaction(function () use ($date, $fingerprint, $method, $actor) {
            $plan = self::settlementPlan($this->report($date, $date));
            if ($plan['adjustments'] === [] || ! hash_equals(self::fingerprint($plan), $fingerprint)) {
                throw ValidationException::withMessages(['settlement' => 'Салдото се промени. Отворете отново прегледа.']);
            }

            $hasNewerPayments = FinanceMovement::posted()->where('occurred_on', '>', $date)
                ->where(fn ($query) => $query->where('kind', FinanceMovement::SETTLEMENT)
                    ->orWhere(fn ($query) => $query->where('kind', FinanceMovement::TRANSFER)
                        ->whereIn('finance_project_id', array_keys($plan['adjustments']))))->exists();

            if ($hasNewerPayments) {
                throw ValidationException::withMessages(['settlement' => 'Има по-ново вътрешно плащане. Изберете по-късна крайна дата, за да го включите.']);
            }

            return FinanceMovement::create([
                'kind' => FinanceMovement::SETTLEMENT,
                'occurred_on' => $date,
                'amount_cents' => array_sum(array_column($plan['transfers'], 'cents')),
                'method' => $method,
                'details' => $plan,
                ...self::actorFields($actor),
            ]);
        });
    }

    public function void(FinanceMovement $movement, string $reason, User $actor): void
    {
        abort_unless($actor->isAdmin(), 403);
        validator(['void_reason' => $reason], ['void_reason' => ['required', 'string', 'max:500']])->validate();
        $this->transaction(function () use ($movement, $reason, $actor) {
            $movement->refresh();
            if (! $movement->voided_at) {
                $movement->update(['voided_at' => now(), 'voided_by_id' => $actor->id, 'void_reason' => $reason]);
            }
        });
    }

    public function transaction(callable $callback): mixed
    {
        return (new FinanceProject)->getConnection()->transaction(function () use ($callback) {
            // Lock before any consistent read. The Filament write pages delegate their transactions here.
            FinanceProject::where('kind', FinanceProject::STUDIO)->lockForUpdate()->firstOrFail(['id']);

            return $callback();
        });
    }

    private static function actorFields(User $actor): array
    {
        abort_unless($actor->isAdmin(), 403);

        return ['recorded_by_id' => $actor->id, 'recorded_by_name' => $actor->name];
    }
}
