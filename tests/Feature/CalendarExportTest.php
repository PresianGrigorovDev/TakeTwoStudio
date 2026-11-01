<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\CalendarExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Добави в календар": Google Calendar link and .ics for Apple / Outlook (2026-11-02). */
class CalendarExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(array $attributes = []): Booking
    {
        $member = TeamMember::create(['name' => 'Симеон Тодоров', 'role_bg' => 'Фотограф', 'is_active' => true, 'display_order' => 1]);
        $booking = Booking::create($attributes + [
            'kind' => Booking::KIND_SESSION,
            'name' => 'Абитуриентска фотосесия – Мария',
            'phone' => '0888123456',
            'event_date' => '2026-11-12',
            'start_time' => '13:30',
            'end_time' => '15:15',
            'location' => 'Морска градина, Варна; вход Казиното',
            'status' => 'confirmed',
        ]);
        $booking->teamMembers()->sync([$member->id]);

        return $booking;
    }

    public function test_ics_uses_utc_from_sofia_time_and_is_valid_text(): void
    {
        $ics = CalendarExport::ics($this->makeSession());

        $this->assertStringContainsString("DTSTART:20261112T113000Z\r\n", $ics, '13:30 in Sofia (EET, UTC+2) = 11:30 UTC');
        $this->assertStringContainsString("DTEND:20261112T131500Z\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:📷 Абитуриентска фотосесия – Мария', $ics);
        $unfolded = str_replace("\r\n ", '', $ics); // long lines are folded (RFC 5545)
        $this->assertStringContainsString('LOCATION:Морска градина\, Варна\; вход Казиното', $unfolded);
        $this->assertStringContainsString('Симеон Тодоров', $unfolded);

        foreach (explode("\r\n", rtrim($ics)) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "line too long: $line");
        }
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }

    public function test_summer_time_and_multi_day_entries(): void
    {
        $booking = $this->makeSession(['kind' => Booking::KIND_BUSY, 'name' => 'Отпуск', 'event_date' => '2027-06-20', 'end_date' => '2027-06-22', 'start_time' => '08:00', 'end_time' => '21:00', 'location' => null]);

        $ics = CalendarExport::ics($booking);
        $this->assertStringContainsString('DTSTART:20270620T050000Z', $ics, '08:00 in Sofia summer (EEST, UTC+3)');
        $this->assertStringContainsString('DTEND:20270622T180000Z', $ics, 'ends on the last day');
        $this->assertStringContainsString('SUMMARY:Зает: Отпуск', $ics);
        $this->assertStringNotContainsString('LOCATION:', $ics);
    }

    public function test_google_link_is_prefilled(): void
    {
        $url = CalendarExport::googleUrl($this->makeSession());

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?action=TEMPLATE', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('20261112T113000Z/20261112T131500Z', $query['dates']);
        $this->assertSame('📷 Абитуриентска фотосесия – Мария', $query['text']);
        $this->assertSame('Морска градина, Варна; вход Казиното', $query['location']);
        $this->assertStringContainsString('Телефон: 0888123456', $query['details']);
    }

    public function test_ics_download_is_for_admins_only(): void
    {
        $booking = $this->makeSession();
        $url = route('admin.booking.ics', $booking);

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="take-two-2026-11-12-'.$booking->id.'.ics"')
            ->assertSee('BEGIN:VEVENT', false);
    }

    public function test_engagements_list_and_page_offer_add_to_calendar(): void
    {
        $booking = $this->makeSession(['event_date' => now()->addDays(5)->toDateString()]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(BookingResource::getUrl('index'))->assertOk()->assertSee('Google Calendar')->assertSee(route('admin.booking.ics', $booking), false);
        $this->get(BookingResource::getUrl('edit', ['record' => $booking]))
            ->assertOk()
            ->assertSee('Google Calendar')
            ->assertSee(route('admin.booking.ics', $booking), false);
    }
}
