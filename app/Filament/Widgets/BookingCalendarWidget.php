<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingCalendarWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingCount = Booking::pending()->count();
        $confirmedThisMonth = Booking::confirmed()
            ->whereBetween('event_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $nextBooking = Booking::confirmed()
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->first();

        return [
            Stat::make('Чакащи резервации', $pendingCount)
                ->description('Нуждаят се от потвърждение')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
            Stat::make('Потвърдени този месец', $confirmedThisMonth)
                ->description(now()->translatedFormat('F Y'))
                ->color('success')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Следваща резервация', $nextBooking ? $nextBooking->event_date->format('d.m.Y') : 'Няма')
                ->description($nextBooking ? $nextBooking->service_type . ' - ' . $nextBooking->name : '')
                ->icon('heroicon-o-arrow-right'),
        ];
    }
}
