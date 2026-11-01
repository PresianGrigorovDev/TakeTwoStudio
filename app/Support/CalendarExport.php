<?php

namespace App\Support;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Support\Carbon;

/**
 * "Add to my calendar" for a team calendar entry: a prefilled Google Calendar link and
 * an .ics file (Apple Calendar on iPhone/Mac, Outlook). Entry times are Sofia wall-clock
 * times ("13:30"); both exports use UTC so phones in any time zone show the right hour.
 */
final class CalendarExport
{
    /** Time zone of the stored entry times (the studio works in Varna). */
    public const TIMEZONE = 'Europe/Sofia';

    public static function googleUrl(Booking $booking): string
    {
        [$start, $end] = self::range($booking);

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => self::title($booking),
            'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
            'details' => self::details($booking),
            'location' => (string) $booking->location,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function ics(Booking $booking): string
    {
        [$start, $end] = self::range($booking);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Take Two Studio 1603//Team calendar//BG',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:booking-'.$booking->id.'@taketwostudio1603.com',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape(self::title($booking)),
            'DESCRIPTION:'.self::escape(self::details($booking)),
        ];

        if (filled($booking->location)) {
            $lines[] = 'LOCATION:'.self::escape($booking->location);
        }

        $lines[] = 'URL:'.BookingResource::getUrl('edit', ['record' => $booking]);
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    public static function filename(Booking $booking): string
    {
        return 'take-two-'.$booking->event_date->format('Y-m-d').'-'.$booking->id.'.ics';
    }

    /** Start on the first day, end on the last day (multi-day entries become one event). */
    private static function range(Booking $booking): array
    {
        $start = Carbon::parse($booking->event_date->toDateString().' '.$booking->start_time, self::TIMEZONE)->utc();
        $end = Carbon::parse($booking->last_date->toDateString().' '.$booking->end_time, self::TIMEZONE)->utc();

        return [$start, $end];
    }

    private static function title(Booking $booking): string
    {
        $prefix = match ($booking->kind) {
            Booking::KIND_SESSION => '📷 ',
            Booking::KIND_BUSY => 'Зает: ',
            Booking::KIND_CLOSED => 'Студиото е затворено: ',
            default => '',
        };

        return $prefix.$booking->name;
    }

    private static function details(Booking $booking): string
    {
        $booking->loadMissing('teamMembers');

        return implode("\n", array_filter([
            $booking->kind_label.($booking->service_type ? ' · '.$booking->service_type : ''),
            $booking->teamMembers->isNotEmpty() ? 'Хора: '.$booking->teamMembers->pluck('name')->implode(', ') : null,
            $booking->phone ? 'Телефон: '.$booking->phone : null,
            $booking->admin_notes ? 'Бележки: '.$booking->admin_notes : null,
            'Take Two Studio 1603: '.BookingResource::getUrl('edit', ['record' => $booking]),
        ]));
    }

    /** RFC 5545 text escaping. */
    private static function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** RFC 5545 line folding at 75 octets, never inside a UTF-8 character. */
    private static function fold(string $line): string
    {
        $out = '';
        $current = '';
        $limit = 75; // continuation lines start with a space, so they get 74
        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $out .= $current."\r\n ";
                $current = '';
                $limit = 74;
            }
            $current .= $char;
        }

        return $out.$current;
    }
}
