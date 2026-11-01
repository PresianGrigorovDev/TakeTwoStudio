<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\CalendarExport;
use Filament\Facades\Filament;

/** .ics download of one calendar entry (Apple Calendar / Outlook); admins only. */
class CalendarExportController extends Controller
{
    public function ics(Booking $booking)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
        }

        abort_unless($user->isAdmin(), 403);

        return response(CalendarExport::ics($booking), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.CalendarExport::filename($booking).'"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
