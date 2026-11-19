<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UtilityBills
{
    public const CATEGORIES = [
        'electricity' => 'Ток',
        'water' => 'Вода',
        'internet' => 'Интернет',
        'heating' => 'Отопление',
        'rent' => 'Наем',
        'fee' => 'Такса',
        'other' => 'Друга сметка',
    ];

    public function __construct(private Ledger $ledger) {}

    public function create(array $input, User $actor): FinanceProject
    {
        abort_unless($actor->isAdmin(), 403);
        [$title, $weights, $data] = $this->prepare($input);

        $payment = $this->ledger->record(
            fn () => FinanceProject::create(['title' => $title, 'kind' => FinanceProject::UTILITY, 'shares' => $weights]),
            ['kind' => FinanceMovement::EXPENSE] + $data,
            $actor,
        );

        return $payment->project;
    }

    public function update(FinanceProject $bill, array $input, User $actor): FinanceProject
    {
        abort_unless($actor->isAdmin() && $bill->isUtility(), 403);
        [$title, $weights, $data, $amount] = $this->prepare($input);

        return $this->ledger->transaction(function () use ($bill, $title, $weights, $data, $amount) {
            $expense = $bill->utilityExpense()->firstOrFail();
            if ($expense->voided_at) {
                throw ValidationException::withMessages(['amount' => 'Сметката е изтрита.']);
            }

            $bill->update(['title' => $title, 'shares' => $weights]);
            $expense->update([
                'amount_cents' => $amount,
                'occurred_on' => $data['occurred_on'],
                'from_account_id' => $data['from_account_id'],
                'handled_by_id' => $data['handled_by_id'] ?? null,
                'method' => $data['method'],
                'details' => array_replace($expense->details ?? [], ['shares' => $weights]),
            ]);

            return $bill;
        });
    }

    public static function formData(FinanceProject $bill): array
    {
        $expense = $bill->utilityExpense;
        [$label, $periodLabel] = array_pad(explode(' · ', $bill->title, 2), 2, null);
        $category = array_search($label, self::CATEGORIES, true);
        $weights = $expense->details['shares'] ?? $bill->shares;
        $equal = array_sum($weights) === count($weights);

        return [
            'category' => $category ?: 'other',
            'period_label' => $category ? $periodLabel : $bill->title,
            'amount' => Euro::decimal($expense->amount_cents),
            'occurred_on' => $expense->occurred_on->toDateString(),
            'from_account_id' => $expense->from_account_id,
            'handled_by_id' => $expense->handled_by_id,
            'method' => $expense->method,
            'split_mode' => $equal ? 'equal' : 'custom',
            'members' => array_keys($weights),
            'allocations' => $equal ? [] : collect($weights)->map(fn ($cents, $account) => [
                'account_id' => $account, 'amount' => Euro::decimal($cents),
            ])->values()->all(),
        ];
    }

    private function prepare(array $input): array
    {
        $data = validator($input, [
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'period_label' => ['nullable', 'string', 'max:120'],
            'amount' => ['required'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.Ledger::today()],
            'from_account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'handled_by_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where('kind', 'member')],
            'method' => ['required', Rule::in(array_keys(FinanceMovement::METHODS))],
            'split_mode' => ['required', Rule::in(['equal', 'custom'])],
            'members' => ['required_if:split_mode,equal', 'nullable', 'array'],
            'allocations' => ['required_if:split_mode,custom', 'nullable', 'array'],
        ])->validate();

        $title = self::CATEGORIES[$data['category']];
        if (filled($data['period_label'] ?? null)) {
            $title .= ' · '.trim($data['period_label']);
        }
        $amount = Euro::parse($data['amount']);

        return [$title, self::weights($amount, $data), $data, $amount];
    }

    /** @return array<int,int> */
    public static function weights(int $cents, array $data): array
    {
        $custom = ($data['split_mode'] ?? 'equal') === 'custom';
        $field = $custom ? 'allocations' : 'members';
        $rows = $custom ? ($data['allocations'] ?? []) : ($data['members'] ?? []);
        $weights = [];
        foreach ($rows as $row) {
            $id = (int) ($custom ? ($row['account_id'] ?? 0) : $row);
            if ($id < 1 || isset($weights[$id])) {
                throw ValidationException::withMessages([$field => 'Изберете различен човек за всеки дял.']);
            }

            $weights[$id] = $custom ? Euro::parse($row['amount'] ?? null, $field) : 1;
        }

        if ($weights === [] || FinanceAccount::where('kind', 'member')->whereKey(array_keys($weights))->count() !== count($weights)) {
            throw ValidationException::withMessages([$field => 'Изберете поне един човек от екипа.']);
        }

        if ($custom && array_sum($weights) !== $cents) {
            throw ValidationException::withMessages([$field => 'Сборът на дяловете трябва да е равен на сумата по сметката.']);
        }

        ksort($weights);

        return $weights;
    }
}
