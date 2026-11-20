<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTeamAssignmentLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_only_the_team_of_a_booking_is_logged(): void
    {
        $booking = Booking::create([
            'name' => 'Клиент', 'event_date' => '2031-08-15', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $this->assertTeamAssignmentChangesAreLogged($booking, EditBooking::class);
    }

    public function test_editing_only_the_team_of_an_order_is_logged(): void
    {
        $order = Order::create(['name' => 'Клиент', 'phone' => '0888123456', 'service_type' => 'Сватба']);

        $this->assertTeamAssignmentChangesAreLogged($order, EditOrder::class);
    }

    private function assertTeamAssignmentChangesAreLogged(Booking|Order $record, string $page): void
    {
        $original = TeamMember::create(['name' => 'Фотограф', 'role_bg' => 'Фотограф', 'is_active' => true]);
        $replacement = TeamMember::create(['name' => 'Видеограф', 'role_bg' => 'Видеограф', 'is_active' => true]);
        $record->teamMembers()->attach($original);
        $this->assertDatabaseCount('activity_logs', 0);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $form = Livewire::test($page, ['record' => $record->id]);
        $form->fillForm(['teamMembers' => [(string) $replacement->id]])->call('save')->assertHasNoFormErrors();

        $this->assertSame([$replacement->id], $record->teamMembers()->pluck('team_members.id')->all());
        $countAfterChange = ActivityLog::count();

        Livewire::test($page, ['record' => $record->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame(0, $record->teamMembers()->detach($original->id));
        $this->assertDatabaseCount('activity_logs', $countAfterChange);

        $assignments = ActivityLog::where('subject_type', $record::class)
            ->where('subject_id', $record->id)
            ->where('action', 'updated')
            ->where('user_id', $admin->id)
            ->orderBy('id')
            ->get()
            ->pluck('properties.team_member_id')
            ->filter()
            ->values()
            ->all();

        $this->assertSame([
            ['old' => $original->id, 'new' => null],
            ['old' => null, 'new' => $replacement->id],
        ], $assignments);
    }
}
