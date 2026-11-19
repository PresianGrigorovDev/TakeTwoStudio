<?php

namespace App\Support;

use App\Filament\Pages\Calendar;
use Filament\Pages\Dashboard;

final class CalendarContext
{
    public const MIN_YEAR = 1001;

    public const MAX_YEAR = 9998;

    public static function fromRequest(): array
    {
        $query = request()->query();

        // Filament lazily mounts dashboard widgets through Livewire.
        if (app('livewire')->isLivewireRequest()) {
            parse_str(parse_url(request()->header('Referer', ''), PHP_URL_QUERY) ?: '', $query);
        }

        return self::fromQuery($query);
    }

    public static function fromQuery(array $query): array
    {
        $year = filter_var($query['year'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::MAX_YEAR]]);
        $month = filter_var($query['month'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $member = filter_var($query['member'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return array_filter([
            'year' => $year && $month ? $year : null,
            'month' => $year && $month ? $month : null,
            'member' => $member ?: null,
            'pending' => in_array($query['pending'] ?? null, [1, '1'], true) ? 1 : null,
            'unassigned' => in_array($query['unassigned'] ?? null, [1, '1'], true) ? 1 : null,
            'availability' => self::validDate($query['availability'] ?? null),
        ]);
    }

    public static function validDate(mixed $date): ?string
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year) ? $date : null;
    }

    public static function returnUrl(): ?string
    {
        $context = self::fromQuery(request()->query());

        return match (request()->query('from')) {
            'calendar' => Dashboard::getUrl($context),
            'calendar-page' => Calendar::getUrl($context),
            default => null,
        };
    }
}
