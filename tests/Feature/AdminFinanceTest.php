<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceOverview;
use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Resources\FinanceMovementResource\Pages\CreateFinanceMovement;
use App\Filament\Resources\FinanceMovementResource\Pages\ListFinanceMovements;
use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Resources\FinanceProjectResource\Pages\CreateFinanceProject;
use App\Filament\Resources\FinanceProjectResource\Pages\EditFinanceProject;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Finance\Ledger;
use App\Support\Finance\Projects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_pages_are_only_available_in_the_admin(): void
    {
        $this->get('/admin/finansi')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/finance-movements')->assertForbidden();
        $this->get('/admin/finansi')->assertForbidden();
    }

    public function test_order_price_fills_the_agreement_and_payment_records_actual_custody_and_author(): void
    {
        $first = $this->member('Симеон');
        $second = $this->member('Пресиан');
        $order = $this->order();
        $order->teamMembers()->attach([$first->team_member_id, $second->team_member_id]);
        $booking = $this->booking($order);
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Християна']);
        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->id])
            ->assertSee(FinanceProjectResource::getUrl('create', ['order' => $order->id]));

        Livewire::withQueryParams(['order' => $order->id])->test(CreateFinanceProject::class)
            ->assertFormSet(['order_id' => $order->id, 'title' => $order->name])
            ->call('create')
            ->assertHasNoFormErrors();
        $project = FinanceProject::where('order_id', $order->id)->sole();
        $this->assertSame($project->id, FinanceProject::forBooking($booking)->id);
        $this->assertSame(150000, $project->agreed_cents);
        $this->assertSame([$first->id => 1, $second->id => 1], $project->shares);
        $this->assertSame(0, $project->movements()->count());

        Livewire::withQueryParams(['project' => $project->id])->test(CreateFinanceMovement::class)
            ->fillForm(['amount' => '300,25', 'method' => 'cash', 'to_account_id' => $first->id])
            ->call('create')
            ->assertHasNoFormErrors();
        $payment = FinanceMovement::sole();

        $this->assertSame(30025, $payment->amount_cents);
        $this->assertSame('deposit', $payment->payment_stage);
        $this->assertSame($first->id, $payment->to_account_id);
        $this->assertSame($admin->id, $payment->recorded_by_id);
        $this->assertSame('Християна', $payment->recorded_by_name);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id, 'subject_type' => FinanceMovement::class, 'subject_id' => $payment->id, 'action' => 'created',
        ]);

        $this->get(FinanceMovementResource::getUrl('view', ['record' => $payment]))
            ->assertOk()->assertSee('300,25 €')->assertSee('Капаро')->assertSee('Християна')->assertSee('Симеон');
        $this->get(FinanceProjectResource::getUrl('edit', ['record' => $project]))
            ->assertOk()->assertSee('1199,75 €');
    }

    public function test_custom_shares_for_a_standalone_booking_and_general_costs_split_exactly(): void
    {
        $first = $this->member('Симеон');
        $second = $this->member('Пресиан');
        $third = $this->member('Християна');
        $booking = $this->booking();
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));

        $component = Livewire::withQueryParams(['booking' => $booking->id])->test(CreateFinanceProject::class)
            ->fillForm(['agreed' => '1000', 'participants' => [
                ['account_id' => $first->id, 'percentage' => '60'],
                ['account_id' => $second->id, 'percentage' => '30'],
            ]])->call('create')->assertHasFormErrors(['participants']);

        $component->fillForm(['participants' => [
            ['account_id' => $first->id, 'percentage' => '60'],
            ['account_id' => $second->id, 'percentage' => '40'],
        ]])->call('create')->assertHasNoFormErrors();
        $project = FinanceProject::where('booking_id', $booking->id)->sole();
        $this->assertSame([$first->id => 6000, $second->id => 4000], $project->shares);

        $this->assertSame([1 => 334, 2 => 333, 3 => 333], Ledger::split(1000, [1 => 1, 2 => 1, 3 => 1]));
        $studio = FinanceProject::studio();
        $this->move($studio, $admin, ['kind' => FinanceMovement::EXPENSE, 'from_account_id' => $first->id, 'amount' => '30.01']);
        $this->move($project, $admin, ['to_account_id' => $second->id, 'amount' => '100.01']);
        $snapshot = app(Ledger::class)->report(Ledger::today(), Ledger::today());

        $this->assertSame([$first->id => 6001, $second->id => 4000], $snapshot['projects'][$project->id]['shares']);
        $this->assertSame([$first->id => -1001, $second->id => -1000, $third->id => -1000], $snapshot['projects'][$studio->id]['shares']);
    }

    public function test_a_partial_project_transfer_and_a_period_settlement_clear_balances_without_counting_as_income(): void
    {
        $first = $this->member('Симеон');
        $second = $this->member('Пресиан');
        $third = $this->member('Християна');
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));
        $project = app(Projects::class)->save(null, [
            'order_id' => $this->order()->id, 'title' => 'Бал',
            'agreed' => '1500', 'participants' => [['account_id' => $first->id], ['account_id' => $second->id]],
        ]);
        $date = Ledger::today();
        $from = today('Europe/Sofia')->startOfMonth()->toDateString();
        $this->move($project, $admin, ['amount' => '1200', 'to_account_id' => $first->id]);
        $this->move($project, $admin, ['kind' => FinanceMovement::EXPENSE, 'amount' => '300', 'from_account_id' => $second->id]);
        $this->move($project, $admin, [
            'kind' => FinanceMovement::TRANSFER, 'amount' => '200',
            'from_account_id' => $first->id, 'to_account_id' => $second->id,
        ]);
        $this->move(FinanceProject::studio(), $admin, ['kind' => FinanceMovement::EXPENSE, 'amount' => '90', 'from_account_id' => $second->id]);
        $ledger = app(Ledger::class);

        $this->assertSame([$first->id => 55000, $second->id => -55000], $ledger->report($from, $date, $project)['balances']);
        $this->assertSame([$first->id => 58000, $second->id => -61000, $third->id => 3000], $ledger->report($from, $date)['balances']);

        Livewire::withQueryParams([])->test(FinanceOverview::class)
            ->assertSee('580,00 €')
            ->assertSee('610,00 €')
            ->callAction('settlePeriod', data: ['confirmed' => true, 'method' => 'bank_transfer'])
            ->assertHasNoActionErrors()
            ->assertSee('Няма суми за уреждане.');

        $settlement = FinanceMovement::where('kind', FinanceMovement::SETTLEMENT)->sole();
        $this->assertSame(61000, $settlement->amount_cents);
        $this->assertSame($admin->id, $settlement->recorded_by_id);
        $this->get(FinanceMovementResource::getUrl('view', ['record' => $settlement]))
            ->assertOk()->assertSee('Симеон → Пресиан')->assertSee('Християна → Пресиан');
        $after = $ledger->report($from, $date);
        $this->assertSame([], array_filter($after['balances']));
        $this->assertSame([], $after['projects'][$project->id]['balance']);
        $this->assertSame([], $after['projects'][FinanceProject::studio()->id]['balance']);
        $this->assertSame(['received' => 120000, 'refunded' => 0, 'expenses' => 39000, 'transfers' => 81000], $after['period']);
        $this->assertSame(30000, $after['unpaid']);

        try {
            $ledger->settle($date, Ledger::fingerprint($settlement->details), null, $admin);
            $this->fail('Duplicate settlement was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settlement', $exception->errors());
        }
    }

    public function test_voiding_preserves_the_record_and_corrections_flow_into_customer_balance(): void
    {
        $first = $this->member('Симеон');
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));
        $project = app(Projects::class)->save(null, [
            'booking_id' => $this->booking()->id, 'title' => 'Фотосесия', 'agreed' => '400',
            'participants' => [['account_id' => $first->id]],
        ]);
        $bank = FinanceAccount::where('key', 'bank')->firstOrFail();
        $this->move($project, $admin, ['to_account_id' => $bank->id, 'amount' => '300']);
        $refund = $this->move($project, $admin, ['kind' => FinanceMovement::REFUND, 'from_account_id' => $bank->id, 'amount' => '50']);
        $date = Ledger::today();

        $this->assertSame(15000, app(Ledger::class)->report($date, $date)['unpaid']);
        Livewire::test(ListFinanceMovements::class)
            ->callTableAction('void', $refund, data: ['void_reason' => 'Дублиран запис'])
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($refund->refresh()->voided_at);
        $this->assertSame('Дублиран запис', $refund->void_reason);
        $this->assertSame(10000, app(Ledger::class)->report($date, $date)['unpaid']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id, 'subject_type' => FinanceMovement::class, 'subject_id' => $refund->id, 'action' => 'updated',
        ]);
    }

    public function test_deleting_the_source_keeps_finance_history_and_existing_shares_editable(): void
    {
        $first = $this->member('Симеон');
        $second = $this->member('Пресиан');
        $order = $this->order();
        $this->actingAs($admin = User::factory()->create(['is_admin' => true]));
        $project = app(Projects::class)->save(null, [
            'order_id' => $order->id, 'title' => 'Сватба', 'participants' => [['account_id' => $first->id]],
        ]);
        $movement = $this->move($project, $admin, ['to_account_id' => $second->id]);

        Livewire::test(EditFinanceProject::class, ['record' => $project->id])
            ->fillForm(['participants' => [['account_id' => $first->id], ['account_id' => $second->id]]])
            ->call('save')->assertHasNoFormErrors();
        Livewire::test(EditOrder::class, ['record' => $order->id])->callAction('delete');
        $this->assertNull($project->refresh()->order_id);
        $this->assertSame([$first->id => 1, $second->id => 1], $project->shares);
        $this->assertSame($project->id, $movement->refresh()->finance_project_id);
        $this->assertSame(['old' => $order->id, 'new' => null], ActivityLog::where('subject_type', FinanceProject::class)
            ->latest('id')->firstOrFail()->properties['order_id']);
    }

    private function member(string $name): FinanceAccount
    {
        $member = TeamMember::create(['name' => $name, 'role_bg' => 'Фотограф', 'is_active' => true]);

        return FinanceAccount::where('team_member_id', $member->id)->sole();
    }

    private function order(): Order
    {
        return Order::create([
            'name' => 'Поръчка за сватба', 'phone' => '0888123456', 'email' => 'client@example.test',
            'service_type' => 'Сватба', 'price' => '1500.00',
        ]);
    }

    private function booking(?Order $order = null): Booking
    {
        return Booking::create([
            'name' => 'Фотосесия', 'event_date' => today()->toDateString(), 'start_time' => '12:00', 'end_time' => '14:00',
            'order_id' => $order?->id, 'status' => 'confirmed',
        ]);
    }

    private function move(FinanceProject $project, User $actor, array $fields): FinanceMovement
    {
        return app(Ledger::class)->record($project, $fields + [
            'kind' => FinanceMovement::INCOME, 'payment_stage' => 'deposit',
            'amount' => '100', 'occurred_on' => Ledger::today(), 'method' => 'cash',
        ], $actor);
    }
}
