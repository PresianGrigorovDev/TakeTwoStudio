<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Widgets\TeamCalendarWidget;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\CalendarExport;
use App\Support\OrderCalendar;
use App\Support\TeamCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_order_and_calendar_share_the_main_schedule_and_team(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = now()->addDays(10);
        $orderFields = ['name' => '12 А клас', 'phone' => '0888123456', 'service_type' => 'Абитуриентски Бал', 'status' => 'accepted'];
        Livewire::test(CreateOrder::class)->fillForm($orderFields + ['event_date' => $date->toDateString()])
            ->call('create')->assertHasNoFormErrors();

        $order = Order::sole();
        $primary = $order->booking()->sole();
        $this->assertNull($primary->start_time);
        $this->assertSame('confirmed', $primary->status);
        $this->assertSame(BookingController::getWorkingHours(), TeamCalendar::availableHours($date->toDateString()));
        $this->assertStringContainsString('DTSTART;VALUE=DATE:'.$date->format('Ymd'), CalendarExport::ics($primary));
        Livewire::test(TeamCalendarWidget::class, ['origin' => 'page'])
            ->set('month', $date->month)->set('year', $date->year)
            ->assertSee('12 А клас')->assertSee('Часът предстои');

        $member = TeamMember::create(['name' => 'Симеон', 'role_bg' => 'Фотограф', 'is_active' => true]);
        $extra = $order->bookings()->create([
            'name' => 'Фотосесия', 'event_date' => $date->copy()->addDays(2), 'kind' => Booking::KIND_SESSION,
            'start_time' => '11:00', 'end_time' => '12:00', 'status' => 'confirmed',
        ]);
        Livewire::test(EditOrder::class, ['record' => $order->id])
            ->assertSee('Основен ангажимент')
            ->fillForm(['start_time' => '15:15', 'end_time' => '17:30', 'teamMembers' => [$member->id]])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('15:15', $primary->refresh()->start_time);
        $this->assertSame([$member->id], $primary->teamMembers()->pluck('team_members.id')->all());

        $newDate = $date->copy()->addDay()->toDateString();
        Livewire::withQueryParams(['from' => 'order'])->test(EditBooking::class, ['record' => $primary->id])
            ->assertSee('Към поръчката')
            ->fillForm(['event_date' => $newDate, 'start_time' => '21:15', 'end_time' => '00:00', 'teamMembers' => [], 'phone' => '0899000000'])
            ->call('save')->assertHasNoFormErrors()
            ->assertRedirect(OrderResource::getUrl('edit', ['record' => $order]));

        $order->refresh();
        $this->assertSame($newDate, $order->event_date->toDateString());
        $this->assertSame('21:15', $order->start_time);
        $this->assertSame('24:00', $order->end_time);
        $this->assertSame('0899000000', $order->phone);
        $this->assertTrue($order->teamMembers()->doesntExist());
        $this->assertSame($primary->id, $order->booking->id);
        $this->assertSame($date->copy()->addDays(2)->toDateString(), $extra->refresh()->event_date->toDateString());
        $this->assertSame('11:00', $extra->start_time);
    }

    public function test_linking_an_existing_timed_entry_replaces_a_date_only_slot_and_carries_its_shoot(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = now()->addDays(10)->toDateString();
        $order = Order::create(['name' => '12 Б клас', 'phone' => '', 'service_type' => 'Абитуриентски Бал', 'status' => 'accepted', 'event_date' => $date]);
        DB::transaction(fn () => OrderCalendar::connectExisting($order));
        $first = $order->booking()->sole();
        $shoot = $order->shoots()->create(['name' => 'Бал', 'kind' => 'event', 'booking_id' => $first->id]);
        $existing = Booking::create([
            'name' => 'Вечер за 12 Б клас', 'event_date' => $date, 'start_time' => '19:30', 'end_time' => '23:00',
            'status' => 'confirmed',
        ]);

        Livewire::test(EditBooking::class, ['record' => $existing->id])
            ->fillForm(['order_id' => $order->id])->call('save')->assertHasNoFormErrors();

        $this->assertSame('12 Б клас', $order->refresh()->name);
        $this->assertSame('19:30', $order->start_time);
        $this->assertSame($existing->id, $order->booking->id);
        $this->assertSame($existing->id, $shoot->refresh()->booking_id);
        $this->assertSame('cancelled', $first->refresh()->status);
        $this->assertSame([$existing->id], TeamCalendar::entries($date, $date)->modelKeys());
    }

    public function test_the_existing_calendar_confirm_action_accepts_the_linked_order(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = now()->addDays(10)->toDateString();
        $order = Order::create([
            'name' => 'Нова поръчка', 'phone' => '0888123456', 'service_type' => 'Сватба', 'event_date' => $date,
            'start_time' => '10:00', 'end_time' => '12:00',
        ]);
        $existing = $order->bookings()->create([
            'name' => $order->name, 'event_date' => $date, 'start_time' => '11:00', 'end_time' => '13:00',
            'status' => 'pending',
        ]);
        DB::transaction(fn () => OrderCalendar::connectExisting($order));

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $existing)->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $existing->refresh()->status);
        $this->assertSame('accepted', $order->refresh()->status);
        $this->assertSame('11:00', $order->start_time);
        $this->assertSame($existing->id, $order->booking->id);
    }
}
