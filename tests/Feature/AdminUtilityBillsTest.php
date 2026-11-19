<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceOverview;
use App\Filament\Resources\FinanceMovementResource\Pages\CreateFinanceMovement;
use App\Filament\Resources\UtilityBillResource;
use App\Filament\Resources\UtilityBillResource\Pages\CreateUtilityBill;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Finance\Ledger;
use App\Support\Finance\UtilityBills;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUtilityBillsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bill_defaults_to_equal_shares_and_records_who_paid(): void
    {
        [$payer, $second, $third] = $this->accounts();
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateUtilityBill::class)
            ->assertFormSet(['split_mode' => 'equal', 'members' => [$payer->id, $second->id, $third->id]])
            ->fillForm($this->billFields($payer))
            ->call('create')
            ->assertHasNoFormErrors();

        $bill = FinanceProject::where('kind', FinanceProject::UTILITY)->sole();
        $expense = $bill->utilityExpense()->sole();
        $this->assertSame(FinanceMovement::EXPENSE, $expense->kind);
        $this->assertSame(9000, $expense->amount_cents);
        $this->assertSame($payer->id, $expense->from_account_id);
        $this->assertSame($admin->id, $expense->recorded_by_id);
        $this->assertSame(
            [$payer->id => -6000, $second->id => 3000, $third->id => 3000],
            $this->report($bill)['balances'],
        );
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'subject_type' => FinanceMovement::class,
            'subject_id' => $expense->id,
            'action' => 'created',
        ]);

        $this->get(UtilityBillResource::getUrl())
            ->assertOk()
            ->assertSee('Ток')
            ->assertSee('Симеон')
            ->assertSee('30,00 €');
        $this->get(FinanceOverview::getUrl(['project' => $bill->id]))
            ->assertOk()
            ->assertSee('Пресиан → Симеон')
            ->assertSee('Християна → Симеон');
    }

    public function test_custom_amounts_must_match_the_bill_and_are_preserved_to_the_cent(): void
    {
        [$payer, $second, $third] = $this->accounts();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $form = Livewire::test(CreateUtilityBill::class)
            ->fillForm([
                'amount' => '90,01',
                'split_mode' => 'custom',
                'allocations' => [
                    ['account_id' => $second->id, 'amount' => '20'],
                    ['account_id' => $third->id, 'amount' => '70'],
                ],
            ] + $this->billFields($payer));

        $form->call('create')->assertHasFormErrors(['allocations']);
        $this->assertDatabaseMissing('finance_projects', ['kind' => FinanceProject::UTILITY]);
        $this->assertDatabaseCount('finance_movements', 0);

        $form->fillForm(['allocations' => [
            ['account_id' => $second->id, 'amount' => '20'],
            ['account_id' => $third->id, 'amount' => '70,01'],
        ]])->call('create')->assertHasNoFormErrors();

        $bill = FinanceProject::where('kind', FinanceProject::UTILITY)->sole();
        $this->assertSame(9001, $bill->utilityExpense()->sole()->amount_cents);
        $this->assertSame(
            [$payer->id => -9001, $second->id => 2000, $third->id => 7001],
            $this->report($bill)['balances'],
        );
    }

    public function test_partial_reimbursements_and_period_settlement_reduce_the_same_bill_only_once(): void
    {
        [$payer, $second, $third] = $this->accounts();
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));
        $bill = app(UtilityBills::class)->create($this->billFields($payer) + [
            'split_mode' => 'equal',
            'members' => [$payer->id, $second->id, $third->id],
        ], $admin);

        Livewire::withQueryParams([
            'project' => $bill->id,
            'kind' => FinanceMovement::TRANSFER,
            'from' => $second->id,
            'to' => $payer->id,
            'amount' => '12.34',
        ])->test(CreateFinanceMovement::class)
            ->fillForm(['method' => 'cash'])
            ->call('create')
            ->assertHasNoFormErrors();

        $reimbursement = $bill->movements()->where('kind', FinanceMovement::TRANSFER)->sole();
        $this->assertSame(1234, $reimbursement->amount_cents);
        $this->assertSame(
            [$payer->id => -4766, $second->id => 1766, $third->id => 3000],
            $this->report($bill)['balances'],
        );

        Livewire::withQueryParams([])->test(FinanceOverview::class)
            ->assertSee('47,66 €')
            ->callAction('settlePeriod', data: ['confirmed' => true, 'method' => 'bank_transfer'])
            ->assertHasNoActionErrors();

        $settlement = FinanceMovement::where('kind', FinanceMovement::SETTLEMENT)->sole();
        $this->assertSame(4766, $settlement->amount_cents);
        $this->assertSame([], array_filter($this->report($bill)['balances']));
        $this->assertSame(['received' => 0, 'refunded' => 0, 'expenses' => 9000, 'transfers' => 6000], $this->report()['period']);
    }

    private function accounts(): array
    {
        return array_map(function (string $name): FinanceAccount {
            $member = TeamMember::create(['name' => $name, 'role_bg' => 'Фотограф', 'is_active' => true]);

            return FinanceAccount::where('team_member_id', $member->id)->sole();
        }, ['Симеон', 'Пресиан', 'Християна']);
    }

    private function billFields(FinanceAccount $payer): array
    {
        return [
            'category' => 'electricity',
            'period_label' => 'Ноември',
            'amount' => '90',
            'occurred_on' => Ledger::today(),
            'from_account_id' => $payer->id,
            'method' => 'bank_transfer',
        ];
    }

    private function report(?FinanceProject $project = null): array
    {
        $today = Ledger::today();

        return app(Ledger::class)->report($today, $today, $project);
    }
}
