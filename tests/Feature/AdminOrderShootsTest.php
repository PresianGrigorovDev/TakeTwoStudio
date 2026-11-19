<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ManageOrderShoots;
use App\Livewire\ServiceHubTable;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderShoot;
use App\Models\PromPackage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\TeamCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderShootsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_configure_a_package_and_override_its_copy_for_one_order(): void
    {
        $order = $this->order();
        $service = Service::firstOrCreate(['slug' => 'proms'], ['name_bg' => 'Абитуриентски балове']);
        $package = PromPackage::create(['name' => 'Лукс', 'price' => 250, 'price_eur' => 128, 'features' => []]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $plan = [
            ['name' => 'Есенна фотосесия', 'kind' => OrderShoot::SESSION],
            ['name' => 'Пролетна фотосесия', 'kind' => OrderShoot::SESSION],
            ['name' => 'Канене на класния', 'kind' => OrderShoot::EVENT],
            ['name' => 'Изпращане', 'kind' => OrderShoot::EVENT],
            ['name' => 'Бал', 'kind' => OrderShoot::EVENT],
        ];
        Livewire::test(ServiceHubTable::class, ['serviceId' => $service->id, 'section' => 'packages'])
            ->callTableAction('edit', $package, data: ['shoot_plan' => $plan])
            ->assertHasNoTableActionErrors();
        $this->assertSame($plan, $package->refresh()->shoot_plan);

        $page = Livewire::test(ManageOrderShoots::class, ['record' => $order->id])
            ->mountTableAction('applyPackage')
            ->setTableActionData(['package' => "proms:{$package->id}"]);
        $draft = $page->get('mountedTableActionsData.0.shoot_plan');
        $this->assertSame($plan, array_values($draft));
        $draft[array_key_first($draft)]['name'] = 'Фотосесия на плажа';

        $page->setTableActionData(['shoot_plan' => $draft])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertSee('Фотосесия на плажа')
            ->assertSee('Фотосесии · 2 от 2 остават')
            ->assertSee('Събития · 3 от 3 остават');

        $package->update(['shoot_plan' => [$plan[1]]]);

        $this->assertSame('Лукс', $order->refresh()->shoot_package_name);
        $this->assertSame(array_column($draft, 'name'), $order->shoots()->orderBy('id')->pluck('name')->all());
        $this->assertSame(2, $order->shoots()->remaining()->where('kind', OrderShoot::SESSION)->count());
        $this->assertSame(3, $order->shoots()->remaining()->where('kind', OrderShoot::EVENT)->count());
        $this->get(OrderResource::getUrl('index'))->assertOk()->assertSee('2 фотосесии · 3 събития');
    }

    public function test_marking_a_shoot_records_the_date_and_admin_and_can_be_reversed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();
        $this->actingAs($admin);
        $page = Livewire::test(ManageOrderShoots::class, ['record' => $order->id])
            ->callTableAction('create', data: ['name' => 'Нощна фотосесия', 'kind' => OrderShoot::SESSION])
            ->assertHasNoTableActionErrors();
        $shoot = $order->shoots()->sole();

        $date = now()->subDays(2)->toDateString();
        $booking = $this->booking($order, ['event_date' => $date]);
        $shoot->update(['booking_id' => $booking->id]);
        $this->assertSame(1, $order->shoots()->remaining()->count());
        $order->update(['event_date' => $date, 'status' => 'accepted']);
        $this->artisan('orders:complete-expired')->assertSuccessful();
        $this->assertSame('accepted', $order->refresh()->status);

        $page->callTableAction('markShot', $shoot, data: ['shot_on' => $date])
            ->assertHasNoTableActionErrors()
            ->assertSee('Фотосесии · 0 от 1 остават');

        $this->assertSame(0, $order->shoots()->remaining()->count());
        $this->assertSame($date, $shoot->refresh()->shot_on->toDateString());
        $this->assertSame($admin->id, $shoot->shot_by_id);
        $entry = ActivityLog::where('subject_type', OrderShoot::class)->where('subject_id', $shoot->id)->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertSame('updated', $entry->action);
        $this->assertArrayHasKey('shot_on', $entry->properties);

        $booking->delete();
        $this->assertNull($shoot->refresh()->booking_id);
        $this->assertSame($date, $shoot->shot_on->toDateString());
        $this->assertSame(0, $order->shoots()->remaining()->count());

        $page->callTableAction('undoShot', $shoot)
            ->assertHasNoTableActionErrors()
            ->assertSee('Фотосесии · 1 от 1 остават');

        $this->assertSame(1, $order->shoots()->remaining()->count());
        $this->assertNull($shoot->refresh()->shot_on);
        $this->assertNull($shoot->shot_by_id);

        $page->callTableAction('markShot', $shoot, data: ['shot_on' => $date])->assertHasNoTableActionErrors();
        $this->artisan('orders:complete-expired')->assertSuccessful();
        $this->assertSame('completed', $order->refresh()->status);
    }

    public function test_an_existing_booking_can_be_linked_to_a_single_shoot_for_its_order(): void
    {
        $order = $this->order();
        $ball = $order->shoots()->create(['name' => 'Бал', 'kind' => OrderShoot::EVENT]);
        $sendoff = $order->shoots()->create(['name' => 'Изпращане', 'kind' => OrderShoot::EVENT]);
        $booking = $this->booking($order);
        $otherBooking = $this->booking($this->order());
        $unassignedBooking = $this->booking(null);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $page = Livewire::test(ManageOrderShoots::class, ['record' => $order->id]);
        $page->callTableAction('connectBooking', $ball, data: ['booking_id' => $otherBooking->id])
            ->assertHasTableActionErrors(['booking_id'])
            ->unmountTableAction()
            ->callTableAction('connectBooking', $ball, data: ['booking_id' => $booking->id])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($booking->orderShoot->is($ball));

        $page->callTableAction('connectBooking', $sendoff, data: ['booking_id' => $booking->id])
            ->assertHasTableActionErrors(['booking_id'])
            ->unmountTableAction();
        $this->assertNull($sendoff->refresh()->booking_id);

        $page->callTableAction('connectBooking', $sendoff, data: ['booking_id' => $unassignedBooking->id])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($unassignedBooking->orderShoot->is($sendoff));

        $booking->update(['status' => 'cancelled']);

        $this->assertSame(2, $order->shoots()->remaining()->count());
        $this->assertTrue($ball->refresh()->canSchedule());
    }

    public function test_scheduling_a_shoot_creates_one_calendar_entry_and_returns_to_its_order(): void
    {
        $order = $this->order();
        $shoot = $order->shoots()->create(['name' => 'Групова фотосесия', 'kind' => OrderShoot::SESSION]);
        $member = TeamMember::create(['name' => 'Фотограф', 'role_bg' => 'Фотограф', 'is_active' => true]);
        $order->teamMembers()->attach($member);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = now()->addDays(15);
        $fields = ['event_date' => $date->toDateString(), 'start_time' => '14:00', 'end_time' => '16:00'];
        $returnUrl = OrderResource::getUrl('shoots', ['record' => $order]);

        $first = Livewire::withQueryParams(['shoot' => $shoot->id])->test(CreateBooking::class)->fillForm($fields);
        $second = Livewire::withQueryParams(['shoot' => $shoot->id])->test(CreateBooking::class)->fillForm($fields);
        $first->assertFormSet(['kind' => Booking::KIND_SESSION, 'teamMembers' => [(string) $member->id]])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect($returnUrl);

        $booking = $shoot->refresh()->booking;
        $this->assertNotNull($booking);
        $this->assertTrue($booking->order->is($order));
        $this->assertSame(Booking::KIND_SESSION, $booking->kind);
        $this->assertTrue($booking->teamMembers->contains($member));
        $this->assertTrue(TeamCalendar::entries($date->toDateString(), $date->toDateString())->contains($booking));

        $second->call('create')->assertRedirect($returnUrl);
        $this->assertDatabaseCount('bookings', 1);

        $date = $date->addDay();
        Livewire::withQueryParams(['from' => 'order-shoot'])->test(EditBooking::class, ['record' => $booking->id])
            ->fillForm(['event_date' => $date->toDateString()])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect($returnUrl);
        $this->get($returnUrl)->assertOk()->assertSee($date->format('d.m.Y'));
    }

    private function order(): Order
    {
        return Order::create(['name' => '12 А клас', 'phone' => '0888123456', 'service_type' => 'Абитуриентски Бал']);
    }

    private function booking(?Order $order, array $attributes = []): Booking
    {
        return Booking::create($attributes + [
            'order_id' => $order?->id,
            'name' => 'Ангажимент', 'event_date' => now()->addWeek()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'confirmed',
        ]);
    }
}
