<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceCompanyAccount;
use App\Filament\Resources\FinanceMovementResource\Pages\CreateFinanceMovement;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Finance\CompanyLedger;
use App\Support\Finance\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCompanyAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_an_order_records_a_due_company_share_until_the_transfer_is_booked(): void
    {
        $member = $this->member();
        $company = FinanceAccount::companyId();
        $order = Order::create($this->orderFields());
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(OrderResource::getUrl('edit', ['record' => $order]))->assertOk()->assertSee('Приеми и запиши плащане');

        Livewire::test(ListOrders::class)
            ->callTableAction('recordPayment', $order, data: $this->paymentFields($member))
            ->assertHasNoTableActionErrors();

        $this->assertSame('accepted', $order->refresh()->status);
        $project = FinanceProject::where('order_id', $order->id)->sole();
        $this->assertSame([], $project->shares);
        $this->assertSame(FinanceMovement::INCOME, $project->movements()->sole()->kind);
        $date = Ledger::today();
        $report = app(CompanyLedger::class)->report($date, $date);
        $this->assertSame(0, $report['balance']);
        $this->assertSame(20000, $report['receivable']);
        $this->get(FinanceCompanyAccount::getUrl())->assertOk()->assertSee('За внасяне')->assertSee('200,00 €');

        Livewire::withQueryParams([
            'project' => $project->id, 'kind' => FinanceMovement::TRANSFER,
            'from' => $member->id, 'to' => $company, 'amount' => '200',
        ])->test(CreateFinanceMovement::class)
            ->fillForm(['method' => 'cash'])->call('create')->assertHasNoFormErrors();

        $report = app(CompanyLedger::class)->report($date, $date);
        $this->assertSame(20000, $report['balance']);
        $this->assertSame(0, $report['receivable']);
    }

    public function test_creating_an_order_with_an_immediate_deposit_records_both_movements_atomically(): void
    {
        $member = $this->member();
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));

        $page = Livewire::test(CreateOrder::class)
            ->fillForm($this->orderFields() + ['teamMembers' => [$member->team_member_id]])
            ->assertSee('Създай и запиши плащане');

        $page->callAction('createWithPayment', data: ['company_percentage' => '101'] + $this->paymentFields($member));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('finance_movements', 0);

        $page->callAction('createWithPayment', data: [
            'company_deposit' => 'now', 'amount' => '880', 'company_percentage' => '15,00',
        ] + $this->paymentFields($member))->assertHasNoActionErrors();

        $order = Order::sole();
        $project = FinanceProject::where('order_id', $order->id)->sole();
        $income = $project->movements()->where('kind', FinanceMovement::INCOME)->sole();
        $deposit = $project->movements()->where('kind', FinanceMovement::TRANSFER)->sole();
        $this->assertSame([$member->id => 1], $project->shares);
        $this->assertSame(88000, $income->amount_cents);
        $this->assertSame(13200, $deposit->amount_cents);
        $this->assertSame(FinanceAccount::companyId(), $deposit->to_account_id);
        $this->assertSame($income->id, $deposit->details['company_receipt_id']);
        $this->assertSame($admin->id, $deposit->recorded_by_id);
        $date = Ledger::today();
        $report = app(CompanyLedger::class)->report($date, $date);
        $this->assertSame(13200, $report['balance']);
        $this->assertSame(0, $report['receivable']);
    }

    public function test_legacy_cash_and_bank_history_share_one_balance_and_include_real_settlement_transfers(): void
    {
        $company = FinanceAccount::companyId();
        $cash = FinanceAccount::companyAccounts()[FinanceAccount::LEGACY_CASH];
        $member = $this->member();
        $actor = User::factory()->create(['is_admin' => true]);
        $this->actingAs($actor);
        $project = FinanceProject::create([
            'title' => 'Стара поръчка', 'kind' => 'order', 'order_id' => Order::create($this->orderFields())->id,
            'shares' => [$member->id => 1],
        ]);

        $base = ['recorded_by_id' => $actor->id, 'recorded_by_name' => $actor->name];
        $project->movements()->create(['kind' => FinanceMovement::INCOME, 'occurred_on' => '2026-09-01', 'amount_cents' => 5000, 'to_account_id' => $cash] + $base);
        $project->movements()->create(['kind' => FinanceMovement::INCOME, 'occurred_on' => '2026-10-02', 'amount_cents' => 8000, 'to_account_id' => $company] + $base);
        $project->movements()->create(['kind' => FinanceMovement::EXPENSE, 'occurred_on' => '2026-10-03', 'amount_cents' => 3000, 'from_account_id' => $cash] + $base);
        $project->movements()->create([
            'kind' => FinanceMovement::TRANSFER, 'occurred_on' => '2026-10-03', 'amount_cents' => 4000,
            'from_account_id' => $company, 'to_account_id' => $cash,
        ] + $base);
        FinanceMovement::create([
            'kind' => FinanceMovement::SETTLEMENT, 'occurred_on' => '2026-10-05', 'amount_cents' => 1000,
            'details' => [
                'transfers' => [['from' => $member->id, 'to' => $cash, 'cents' => 1000]],
                'adjustments' => [$project->id => [$member->id => -1000, $cash => 1000]],
            ],
        ] + $base);

        $report = app(CompanyLedger::class)->report('2026-10-01', '2026-10-31');
        $this->assertSame(5000, $report['opening']);
        $this->assertSame(9000, $report['incoming']);
        $this->assertSame(3000, $report['outgoing']);
        $this->assertSame(11000, $report['balance']);
        $this->assertSame(4, $report['rows']->total());
        $held = app(Ledger::class)->report('2026-10-01', '2026-10-31', $project)['projects'][$project->id]['held'];
        $this->assertSame(11000, $held[$company]);
        $this->assertArrayNotHasKey($cash, $held);
        $this->assertArrayNotHasKey($cash, FinanceAccount::choices());
        $this->assertSame('Фирмена сметка', FinanceAccount::choices(includeLegacy: true)[$cash]);

        Livewire::test(FinanceCompanyAccount::class)
            ->fillForm(['from' => '2026-10-01', 'through' => '2026-10-31'])
            ->call('applyFilters')->assertHasNoFormErrors()
            ->assertSee('110,00 €')->assertSee('Вътре в сметката');
    }

    private function member(): FinanceAccount
    {
        $member = TeamMember::create(['name' => 'Симеон', 'role_bg' => 'Фотограф', 'is_active' => true]);

        return FinanceAccount::where('team_member_id', $member->id)->sole();
    }

    private function orderFields(): array
    {
        return ['name' => 'Поръчка за сватба', 'status' => 'new', 'phone' => '0888123456', 'service_type' => 'Сватба', 'price' => '1500'];
    }

    private function paymentFields(FinanceAccount $account): array
    {
        return [
            'amount' => '1000', 'occurred_on' => Ledger::today(), 'to_account_id' => $account->id,
            'method' => 'cash', 'payment_stage' => 'deposit', 'company_percentage' => '20', 'company_deposit' => 'pending',
        ];
    }
}
