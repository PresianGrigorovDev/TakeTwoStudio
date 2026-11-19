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
        $weights = self::weights(Euro::parse($data['amount']), $data);

        return $this->ledger->transaction(function () use ($title, $weights, $data, $actor) {
            $bill = FinanceProject::create(['title' => $title, 'kind' => FinanceProject::UTILITY, 'shares' => $weights]);
            $this->ledger->record($bill, ['kind' => FinanceMovement::EXPENSE] + $data, $actor);

            return $bill;
        });
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
