<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Filament\Widgets\TeamAvailabilityWidget;
use App\Filament\Widgets\TeamCalendarWidget;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\TeamCalendar;
use Filament\Pages\Dashboard;
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
        $this->entry([
            'kind' => Booking::KIND_CLOSED,
            'name' => 'Затворено',
            'start_time' => sprintf('%02d:00', BookingController::getWorkStart()),
            'end_time' => sprintf('%02d:00', BookingController::getWorkEnd()),
        ]);

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

    public function test_calendar_can_jump_to_a_month_and_year_without_losing_the_person_filter(): void
    {
        [$simeon, $presian] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $year = now()->year + 3;
        $attributes = ['event_date' => $year.'-01-12', 'start_time' => '10:00', 'end_time' => '14:00'];
        $this->entry(['name' => 'Сватба в избрания месец'] + $attributes, [$simeon]);
        $this->entry(['name' => 'Фотосесия на колега'] + $attributes, [$presian]);

        Livewire::test(TeamCalendarWidget::class, ['origin' => 'page'])
            ->assertSee('aria-label="Месец"', false)
            ->assertSee('aria-label="Година"', false)
            ->call('filterMember', $simeon->id)
            ->call('selectPeriod', '1')
            ->call('selectPeriod', null, (string) $year)
            ->assertSet('year', $year)
            ->assertSet('month', 1)
            ->assertSet('member', $simeon->id)
            ->assertSee('Януари '.$year)
            ->assertSee('Сватба в избрания месец')
            ->assertDontSee('Фотосесия на колега')
            ->call('selectPeriod', null, '')
            ->assertHasErrors('year')
            ->assertSet('year', $year)
            ->call('previousMonth')
            ->assertHasNoErrors()
            ->assertSet('month', 12)
            ->assertSet('year', $year - 1)
            ->call('currentMonth')
            ->assertSet('month', now()->month)
            ->assertSet('year', now()->year);
    }

    public function test_editing_an_entry_returns_to_the_active_calendar_period_and_filters(): void
    {
        [$simeon] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $booking = $this->entry([
            'event_date' => '2031-08-15', 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'pending',
        ], [$simeon]);
        $context = ['year' => 2031, 'month' => 8, 'member' => $simeon->id, 'pending' => 1];

        foreach ([['page', 'calendar-page', Calendar::getUrl($context)], ['dashboard', 'calendar', Dashboard::getUrl($context)]] as [$origin, $from, $return]) {
            Livewire::withQueryParams([])->test(TeamCalendarWidget::class, ['origin' => $origin])
                ->call('selectPeriod', 8, 2031)
                ->call('filterMember', $simeon->id)
                ->call('togglePending')
                ->assertSee(BookingResource::getUrl('edit', ['record' => $booking, 'from' => $from] + $context));

            Livewire::withQueryParams(['from' => $from] + $context)
                ->test(EditBooking::class, ['record' => $booking->id])
                ->assertSee($return)
                ->fillForm(['name' => 'Редактиран ангажимент'])
                ->call('save')
                ->assertHasNoFormErrors()
                ->assertRedirect($return);
        }

        Livewire::withQueryParams($context)->test(TeamCalendarWidget::class)
            ->assertSet('year', 2031)
            ->assertSet('month', 8)
            ->assertSet('member', $simeon->id)
            ->assertSet('pendingOnly', true)
            ->assertSee('Редактиран ангажимент');

        Livewire::withQueryParams(['year' => ['2031'], 'month' => 200])->test(TeamCalendarWidget::class)
            ->assertSet('year', now()->year)
            ->assertSet('month', now()->month);
    }

    public function test_a_calendar_day_sets_availability_even_without_bookings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = '2031-08-21';
        $context = ['year' => 2031, 'month' => 8, 'availability' => $date];

        Livewire::test(TeamCalendarWidget::class, ['origin' => 'page'])
            ->call('selectPeriod', 8, 2031)
            ->assertSee('Кой е свободен на 21 август 2031')
            ->call('checkAvailability', '2031-02-30')
            ->assertNotDispatched('calendar-availability-selected')
            ->call('checkAvailability', $date)
            ->assertDispatched('calendar-availability-selected', date: $date, context: $context);

        Livewire::test(TeamAvailabilityWidget::class)
            ->assertSet('to', '00:00')
            ->dispatch('calendar-availability-selected', date: $date, context: $context)
            ->assertSet('date', $date)
            ->assertSee('Свободен')
            ->assertSee('end=24%3A00', false)
            ->assertDispatched('calendar-availability-ready');

        Livewire::test(TeamCalendarWidget::class)
            ->call('selectPeriod', 8, 2031)
            ->call('checkAvailability', $date)
            ->assertRedirect(Calendar::getUrl($context).'#team-availability');

        Livewire::withQueryParams($context)->test(TeamAvailabilityWidget::class)->assertSet('date', $date);
    }

    public function test_follow_up_filters_show_only_matching_client_engagements(): void
    {
        [$simeon] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $day = ['event_date' => '2031-08-12', 'start_time' => '10:00', 'end_time' => '12:00'];
        $this->entry(['name' => 'Чакащ с екип', 'status' => 'pending'] + $day, [$simeon]);
        $this->entry(['name' => 'Чакащ без екип', 'status' => 'pending', 'kind' => Booking::KIND_SESSION] + $day);
        $this->entry(['name' => 'Потвърден без екип'] + $day);
        $this->entry(['name' => 'Затворено студио', 'kind' => Booking::KIND_CLOSED] + $day);

        Livewire::test(TeamCalendarWidget::class)->call('selectPeriod', 8, 2031)
            ->call('filterMember', $simeon->id)
            ->call('togglePending')
            ->assertSee('Чакащ с екип')
            ->assertDontSee('Потвърден без екип')
            ->call('toggleUnassigned')
            ->assertSet('member', null)
            ->assertSee('Чакащ без екип')
            ->assertDontSee('Чакащ с екип')
            ->assertDontSee('Потвърден без екип')
            ->assertDontSee('Затворено студио')
            ->call('togglePending')
            ->assertSee('Потвърден без екип')
            ->assertDontSee('Затворено студио');
    }

    public function test_search_by_name_formatted_phone_and_location_opens_the_matching_booking(): void
    {
        [$simeon, $presian] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $booking = $this->entry([
            'name' => 'Мария Иванова', 'phone' => '+359 (888) 12-34-56', 'location' => 'Ресторант Морско конче',
            'event_date' => '2031-08-12', 'start_time' => '10:00', 'end_time' => '12:00',
        ], [$presian]);
        $this->entry(['name' => 'Мария Отказана', 'status' => 'rejected', 'start_time' => '10:00', 'end_time' => '12:00']);

        Livewire::test(TeamCalendarWidget::class)->call('selectPeriod', 1, 2031)
            ->call('filterMember', $simeon->id)
            ->call('togglePending')
            ->set('search', 'мария')->assertSee('Мария Иванова')->assertDontSee('Мария Отказана')
            ->set('search', '0888 12 34 56')->assertSee('Мария Иванова')
            ->set('search', 'морско конче')->assertSee('Мария Иванова')
            ->call('selectBooking', $booking->id)
            ->assertSet('search', '')
            ->assertSet('year', 2031)->assertSet('month', 8)
            ->assertSet('member', null)->assertSet('pendingOnly', false)
            ->assertSet('selectedBooking', $booking->id)
            ->assertSee('is-selected', false)
            ->assertDispatched('calendar-booking-selected');
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

    public function test_photo_session_is_client_work_with_minutes_and_location(): void
    {
        [$simeon] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'kind' => Booking::KIND_SESSION,
                'name' => 'Абитуриентска фотосесия – Мария',
                'phone' => '0888123456',
                'status' => 'confirmed',
                'teamMembers' => [$simeon->id],
                'event_date' => $this->day,
                'start_time' => '13:30',
                'end_time' => '15:15',
                'location' => 'Морска градина, Варна',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $session = Booking::where('kind', Booking::KIND_SESSION)->sole();
        $this->assertSame(['0888123456', '13:30', '15:15', 'Морска градина, Варна'], [$session->phone, $session->start_time, $session->end_time, $session->location]);
        $this->assertSame(['13:00', '14:00', '15:00'], $session->occupiedHours());

        $this->assertCount(1, TeamCalendar::conflicts($this->day, null, '15:00', '16:00', [$simeon->id]), '15:00 overlaps 13:30–15:15');
        $this->assertCount(0, TeamCalendar::conflicts($this->day, null, '15:15', '16:00', [$simeon->id]), 'back-to-back at 15:15 is fine');

        $date = now()->addDays(10);
        Livewire::test(TeamCalendarWidget::class)
            ->set('year', $date->year)
            ->set('month', $date->month)
            ->assertSee('📷')
            ->assertSee('Морска градина, Варна')
            ->assertSee('13:30–15:15');
    }

    public function test_unassigned_photo_session_still_takes_a_place(): void
    {
        foreach (range(1, 3) as $i) {
            $this->entry(['kind' => Booking::KIND_SESSION, 'name' => "Сесия $i", 'start_time' => '09:00', 'end_time' => '10:00']);
        }

        $this->assertNotContains('09:00', TeamCalendar::availableHours($this->day));
    }

    public function test_calendar_page_shows_who_is_free_and_where_the_others_are(): void
    {
        [$simeon, $presian] = $this->team;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->entry(['name' => 'Сватба – Мария и Иван', 'start_time' => '12:00', 'end_time' => '20:00', 'location' => 'Ресторант „Морско конче“'], [$simeon]);

        $this->get(\App\Filament\Pages\Calendar::getUrl())->assertOk()->assertSee('Календар на екипа');

        Livewire::test(TeamAvailabilityWidget::class)
            ->set('date', $this->day)
            ->set('from', '13:00')
            ->set('to', '15:00')
            ->assertSee('Сватба – Мария и Иван')
            ->assertSee('Ресторант „Морско конче“')
            ->assertSee('Свободен')
            ->assertSee('assignee='.$presian->id, false)
            ->set('from', '20:00')
            ->set('to', '21:00')
            ->assertDontSee('Сватба – Мария и Иван');
    }

    public function test_saving_from_the_calendar_page_returns_to_it(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::withQueryParams(['from' => 'calendar-page', 'date' => $this->day, 'start' => '10:30', 'end' => '12:00'])
            ->test(CreateBooking::class)
            ->assertFormSet(['start_time' => '10:30', 'end_time' => '12:00'])
            ->fillForm(['kind' => Booking::KIND_CLIENT, 'name' => 'Клиент'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(\App\Filament\Pages\Calendar::getUrl());
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
