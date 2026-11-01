<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Filament\Widgets\TeamCalendarWidget;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\TeamCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Internal team calendar (2026-11-02): several people per entry, several entries per
 * day, personal busy time, studio closed, conflicts, public /booking availability.
 */
class TeamCalendarTest extends TestCase
{
    use RefreshDatabase;

    private string $day;

    /** @var array<int,TeamMember> */
    private array $team;

    protected function setUp(): void
    {
        parent::setUp();

        TeamMember::query()->delete();
        $this->team = [
            TeamMember::create(['name' => 'Симеон Тодоров', 'role_bg' => 'Фотограф', 'is_active' => true, 'display_order' => 1, 'calendar_color' => '#2563eb']),
            TeamMember::create(['name' => 'Пресиан Григоров', 'role_bg' => 'Видеограф', 'is_active' => true, 'display_order' => 2, 'calendar_color' => '#db2777']),
            TeamMember::create(['name' => 'Християна Гинчева', 'role_bg' => 'Фотограф', 'is_active' => true, 'display_order' => 3, 'calendar_color' => '#059669']),
        ];
        $this->day = now()->addDays(10)->toDateString();
    }

    private function entry(array $attributes, array $people = []): Booking
    {
        $booking = Booking::create($attributes + ['event_date' => $this->day, 'status' => 'confirmed', 'name' => 'Запис']);
        $booking->teamMembers()->sync(array_map(fn (TeamMember $m) => $m->id, $people));

        return $booking->load('teamMembers');
    }

    public function test_two_people_on_one_engagement_and_several_entries_per_day(): void
    {
        [$simeon, $presian, $hristiana] = $this->team;

        $this->entry(['name' => 'Сватба – Мария и Иван', 'start_time' => '10:00', 'end_time' => '14:00'], [$simeon, $presian]);
        $this->entry(['name' => 'Продуктова сесия', 'start_time' => '16:00', 'end_time' => '18:00'], [$simeon]);

        $slots = TeamCalendar::occupancy($this->day, TeamCalendar::entries($this->day, $this->day));
        $this->assertEqualsCanonicalizing([$simeon->id, $presian->id], $slots['10:00']['members']);
        $this->assertSame([$simeon->id], $slots['16:00']['members']);

        // Християна is still free at 10:00, so the public page keeps the hour open...
        $this->assertContains('10:00', TeamCalendar::availableHours($this->day));

        // ...until she is busy too.
        $this->entry(['kind' => Booking::KIND_BUSY, 'name' => 'Отпуск', 'start_time' => '08:00', 'end_time' => '21:00'], [$hristiana]);
        $this->assertNotContains('10:00', TeamCalendar::availableHours($this->day));
        $this->assertContains('15:00', TeamCalendar::availableHours($this->day));
    }

    public function test_unassigned_engagement_takes_one_place_and_closed_studio_blocks_everybody(): void
    {
        foreach (range(1, 3) as $i) {
            $this->entry(['name' => "Заявка $i", 'status' => 'pending', 'start_time' => '09:00', 'end_time' => '10:00']);
        }
        $this->entry(['kind' => Booking::KIND_CLOSED, 'name' => 'Ремонт', 'start_time' => '12:00', 'end_time' => '14:00']);

        $free = TeamCalendar::availableHours($this->day);
        $this->assertNotContains('09:00', $free, 'three unassigned requests fill three people');
        $this->assertNotContains('12:00', $free);
        $this->assertNotContains('13:00', $free);
        $this->assertContains('14:00', $free);
    }

    public function test_multi_day_busy_time_covers_every_day(): void
    {
        [$simeon] = $this->team;
        $this->entry(['kind' => Booking::KIND_BUSY, 'name' => 'Отпуск', 'end_date' => now()->addDays(12)->toDateString(), 'start_time' => '08:00', 'end_time' => '21:00'], [$simeon]);

        $middle = now()->addDays(11)->toDateString();
        $this->assertCount(1, TeamCalendar::entries($middle, $middle, $simeon->id));
        $this->assertSame([$simeon->id], TeamCalendar::occupancy($middle, TeamCalendar::entries($middle, $middle))['12:00']['members']);
        $this->assertCount(0, TeamCalendar::entries(now()->addDays(13)->toDateString(), now()->addDays(13)->toDateString()));
    }

    public function test_conflicts_name_the_double_booked_person(): void
    {
        [$simeon, , $hristiana] = $this->team;
        $wedding = $this->entry(['name' => 'Сватба', 'start_time' => '10:00', 'end_time' => '14:00'], [$simeon]);
        $this->entry(['name' => 'Отменена', 'status' => 'cancelled', 'start_time' => '10:00', 'end_time' => '14:00'], [$hristiana]);

        $conflicts = TeamCalendar::conflicts($this->day, null, '13:00', '15:00', [$simeon->id, $hristiana->id]);
        $this->assertSame([$wedding->id], $conflicts->modelKeys(), 'cancelled entries do not count');
        $this->assertStringStartsWith('Симеон Тодоров – Сватба', TeamCalendar::describeConflicts($conflicts, [$simeon->id])[0]);

        $this->assertCount(0, TeamCalendar::conflicts($this->day, null, '14:00', '16:00', [$simeon->id]), 'back-to-back is fine');
        $this->assertCount(0, TeamCalendar::conflicts($this->day, null, '10:00', '14:00', [$simeon->id], $wedding->id), 'an entry never conflicts with itself');
    }

    public function test_public_booking_api_reads_the_team_calendar(): void
    {
        $this->entry(['kind' => Booking::KIND_CLOSED, 'name' => 'Затворено', 'start_time' => '08:00', 'end_time' => '21:00']);

        $this->getJson('/api/booking-hours?date='.$this->day)->assertOk()->assertExactJson([]);

        $month = now()->addDays(10);
        $this->getJson("/api/booking-availability?year={$month->year}&month={$month->month}")
            ->assertOk()
            ->assertJsonPath($this->day, 'unavailable');

        $this->post('/submit-booking', [
            'name' => 'Клиент', 'phone' => '0888123456', 'event_date' => $this->day,
            'start_time' => '10:00', 'end_time' => '12:00', 'service_type' => 'Сватба', 'gdpr_consent' => '1',
        ])->assertSessionHasErrors('start_time');
    }

    public function test_dashboard_widget_shows_entries_people_and_filters_by_person(): void
    {
        [$simeon, $presian] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->entry(['name' => 'Сватба – Мария и Иван', 'start_time' => '10:00', 'end_time' => '14:00'], [$simeon, $presian]);
        $this->entry(['name' => 'Портрет', 'start_time' => '15:00', 'end_time' => '16:00'], [$presian]);

        $date = now()->addDays(10);
        Livewire::test(TeamCalendarWidget::class)
            ->set('year', $date->year)
            ->set('month', $date->month)
            ->assertSee('Сватба – Мария и Иван')
            ->assertSee('Портрет')
            ->assertSee('Симеон')
            ->assertSee('Пресиан')
            ->call('filterMember', $simeon->id)
            ->assertSee('Сватба – Мария и Иван')
            ->assertDontSee('15:00–16:00');
    }

    public function test_admin_can_create_personal_busy_time_without_client_fields(): void
    {
        [$simeon] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'kind' => Booking::KIND_BUSY,
                'name' => 'Отпуск',
                'teamMembers' => [$simeon->id],
                'event_date' => $this->day,
                'end_date' => now()->addDays(12)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '21:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $busy = Booking::where('kind', Booking::KIND_BUSY)->with('teamMembers')->sole();
        $this->assertSame('confirmed', $busy->status);
        $this->assertNull($busy->phone);
        $this->assertSame([$simeon->id], $busy->teamMembers->modelKeys());
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateBooking::class)
            ->fillForm(['kind' => Booking::KIND_CLIENT, 'name' => 'Сватба', 'event_date' => $this->day, 'start_time' => '14:00', 'end_time' => '10:00'])
            ->call('create')
            ->assertHasFormErrors(['end_time']);
    }

    public function test_blocked_dates_become_closed_entries(): void
    {
        DB::table('blocked_dates')->insert([
            ['date' => '2027-05-20', 'blocked_hours' => null, 'reason' => 'Сватба извън града', 'created_at' => now(), 'updated_at' => now()],
            ['date' => '2027-05-21', 'blocked_hours' => json_encode(['09:00', '10:00', '14:00']), 'reason' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        (require database_path('migrations/2026_11_02_153000_import_blocked_dates_into_calendar.php'))->up();

        $closed = Booking::where('kind', Booking::KIND_CLOSED)->orderBy('event_date')->orderBy('start_time')->get();
        $this->assertSame(
            [['2027-05-20', '08:00', '21:00', 'Сватба извън града'], ['2027-05-21', '09:00', '11:00', 'Блокиран ден'], ['2027-05-21', '14:00', '15:00', 'Блокиран ден']],
            $closed->map(fn ($b) => [$b->event_date->toDateString(), $b->start_time, $b->end_time, $b->name])->all()
        );
    }

    public function test_pivot_migration_recovers_from_the_half_finished_production_run(): void
    {
        [$simeon, $presian] = $this->team;
        $a = $this->entry(['name' => 'А', 'start_time' => '10:00', 'end_time' => '12:00'], [$simeon]);
        $b = $this->entry(['name' => 'Б', 'start_time' => '12:00', 'end_time' => '14:00']);

        // Production state on 2026-11-02: pivot created and filled, then the drop failed;
        // bookings.team_member_id still there with an index but WITHOUT a foreign key.
        \Illuminate\Support\Facades\Schema::table('bookings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('team_member_id')->nullable();
            $table->index('team_member_id', 'bookings_team_member_id_foreign');
        });
        DB::table('bookings')->where('id', $a->id)->update(['team_member_id' => $simeon->id]);
        DB::table('bookings')->where('id', $b->id)->update(['team_member_id' => $presian->id]);

        $migration = require database_path('migrations/2026_11_02_151000_create_booking_team_member_table.php');
        $migration->up();
        $migration->up(); // and once more: must be a no-op

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'team_member_id'));
        $this->assertSame([$simeon->id], $a->fresh()->teamMembers->modelKeys(), 'no duplicate pivot rows');
        $this->assertSame([$presian->id], $b->fresh()->teamMembers->modelKeys(), 'rows not copied before are copied now');
    }

    public function test_order_copies_are_merged_into_one_entry_with_both_people(): void
    {
        [$simeon, $presian] = $this->team;
        $orderId = DB::table('orders')->insertGetId(['name' => 'Мария', 'phone' => '0888', 'service_type' => 'Сватба', 'created_at' => now(), 'updated_at' => now()]);

        $a = $this->entry(['name' => 'Мария', 'order_id' => $orderId, 'start_time' => '10:00', 'end_time' => '18:00'], [$simeon]);
        $this->entry(['name' => 'Мария', 'order_id' => $orderId, 'start_time' => '10:00', 'end_time' => '18:00'], [$presian]);

        (require database_path('migrations/2026_11_02_152000_merge_duplicate_order_bookings.php'))->up();

        $this->assertSame([$a->id], Booking::pluck('id')->all());
        $this->assertEqualsCanonicalizing([$simeon->id, $presian->id], $a->fresh()->teamMembers->modelKeys());
    }
}
