<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CompanyLedger
{
    public function __construct(private Ledger $ledger) {}

    public function report(string $from, string $through, int $page = 1): array
    {
        $company = FinanceAccount::companyId();
        $accounts = array_values(FinanceAccount::companyAccounts());
        $opening = $incoming = $outgoing = $total = 0;
        $rows = [];
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $movements = FinanceMovement::posted()->where('occurred_on', '<=', $through)
            ->where(fn ($query) => $query->whereIn('from_account_id', $accounts)
                ->orWhereIn('to_account_id', $accounts)->orWhere('kind', FinanceMovement::SETTLEMENT))
            ->orderByDesc('occurred_on')->orderByDesc('id')->cursor();
        foreach ($movements as $movement) {
            $amounts = self::amounts($movement, $accounts);
            if ($amounts === null) {
                continue;
            }

            [$in, $out] = $amounts;
            if ($movement->occurred_on->toDateString() < $from) {
                $opening += $in - $out;

                continue;
            }

            $incoming += $in;
            $outgoing += $out;
            if ($total++ >= $offset && count($rows) < $perPage) {
                $rows[] = ['movement' => $movement, 'incoming' => $in, 'outgoing' => $out];
            }
        }

        (new Collection(array_column($rows, 'movement')))->load(['project', 'fromAccount.teamMember', 'toAccount.teamMember']);
        $pending = [];
        $payable = 0;
        $balances = $this->ledger->report($from, $through)['projects'];
        foreach ($balances as $projectId => $project) {
            $balance = $project['balance'][$company] ?? 0;
            if ($balance < 0) {
                foreach (Ledger::transfers($project['balance']) as $transfer) {
                    if ($transfer['to'] === $company) {
                        $pending[] = $transfer + ['project_id' => $projectId];
                    }
                }
            } else {
                $payable += $balance;
            }
        }

        return [
            'opening' => $opening,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'balance' => $opening + $incoming - $outgoing,
            'pending' => $pending,
            'receivable' => array_sum(array_column($pending, 'cents')),
            'payable' => $payable,
            'projectNames' => FinanceProject::whereKey(array_column($pending, 'project_id'))->pluck('title', 'id'),
            'rows' => new LengthAwarePaginator($rows, $total, $perPage, $page, ['path' => request()->url()]),
        ];
    }

    private static function amounts(FinanceMovement $movement, array $accounts): ?array
    {
        $transfers = $movement->kind === FinanceMovement::SETTLEMENT
            ? ($movement->details['transfers'] ?? [])
            : [['from' => $movement->from_account_id, 'to' => $movement->to_account_id, 'cents' => $movement->amount_cents]];
        $incoming = $outgoing = 0;
        $visible = false;

        foreach ($transfers as $transfer) {
            $fromCompany = in_array((int) $transfer['from'], $accounts, true);
            $toCompany = in_array((int) $transfer['to'], $accounts, true);
            $visible = $visible || $fromCompany || $toCompany;
            if ($fromCompany !== $toCompany) {
                if ($toCompany) {
                    $incoming += (int) $transfer['cents'];
                } else {
                    $outgoing += (int) $transfer['cents'];
                }
            }
        }

        return $visible ? [$incoming, $outgoing] : null;
    }
}
