<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /** Начален час на работния ден */
    const WORK_START = 8;   // 08:00

    /** Краен час на работния ден */
    const WORK_END = 21;    // 21:00

    /** Брой фотографи в екипа */
    const TEAM_CAPACITY = 3;

    public static function getWorkStart(): int
    {
        return self::WORK_START;
    }

    public static function getWorkEnd(): int
    {
        return self::WORK_END;
    }

    public static function getWorkingHours(): array
    {
        $hours = [];
        for ($h = self::WORK_START; $h < self::WORK_END; $h++) {
            $hours[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }
        return $hours;
    }

    public function showCalendar()
    {
        return view('booking');
    }

    /**
     * GET /api/booking-availability?year=&month=
     * Returns per-date status for the calendar grid.
     */
    public function getAvailability(Request $request)
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $today = now()->startOfDay();
        $allHours = self::getWorkingHours();
        $totalSlots = count($allHours);

        // Confirmed bookings grouped by date
        $bookings = Booking::confirmed()
            ->whereBetween('event_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($b) => $b->event_date->format('Y-m-d'));

        // Pending bookings also occupy slots (prevent double-booking while awaiting confirmation)
        $pendingBookings = Booking::pending()
            ->whereBetween('event_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($b) => $b->event_date->format('Y-m-d'));

        // Blocked dates
        $blockedDates = BlockedDate::whereBetween('date', [$startOfMonth, $endOfMonth])->get()
            ->keyBy(fn ($bd) => $bd->date->format('Y-m-d'));

        $availability = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $key = $date->format('Y-m-d');

            if ($date->lt($today)) {
                $availability[$key] = 'past';
                continue;
            }

            // Check blocked dates
            if (isset($blockedDates[$key])) {
                $bd = $blockedDates[$key];
                if ($bd->isFullDayBlocked()) {
                    $availability[$key] = 'unavailable';
                    continue;
                }
            }

            // Count how many team members are booked per hour
            $hourBookingCount = [];
            foreach ([$bookings, $pendingBookings] as $group) {
                if (isset($group[$key])) {
                    foreach ($group[$key] as $booking) {
                        $start = (int) substr($booking->start_time, 0, 2);
                        $end = (int) substr($booking->end_time, 0, 2);
                        for ($h = $start; $h < $end; $h++) {
                            $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                            $hourBookingCount[$hourStr] = ($hourBookingCount[$hourStr] ?? 0) + 1;
                        }
                    }
                }
            }

            // Blocked hours count as all team members blocked
            if (isset($blockedDates[$key]) && !$blockedDates[$key]->isFullDayBlocked()) {
                foreach ($blockedDates[$key]->blocked_hours as $bh) {
                    $hourBookingCount[$bh] = self::TEAM_CAPACITY;
                }
            }

            // A slot is fully unavailable only when all team members are occupied
            $fullyBookedCount = 0;
            $partiallyBookedCount = 0;
            foreach ($allHours as $hour) {
                $count = $hourBookingCount[$hour] ?? 0;
                if ($count >= self::TEAM_CAPACITY) {
                    $fullyBookedCount++;
                } elseif ($count > 0) {
                    $partiallyBookedCount++;
                }
            }

            if ($fullyBookedCount >= $totalSlots) {
                $availability[$key] = 'unavailable';
            } elseif ($fullyBookedCount > 0 || $partiallyBookedCount > 0) {
                $availability[$key] = 'partial';
            } else {
                $availability[$key] = 'available';
            }
        }

        return response()->json($availability);
    }

    /**
     * GET /api/booking-hours?date=2026-06-15
     * Returns available hours for a specific date.
     */
    public function getAvailableHours(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->input('date');
        $allHours = self::getWorkingHours();

        // Blocked hours
        $blockedDate = BlockedDate::where('date', $date)->first();
        if ($blockedDate && $blockedDate->isFullDayBlocked()) {
            return response()->json([]); // whole day blocked
        }

        $blockedHours = $blockedDate ? ($blockedDate->blocked_hours ?? []) : [];

        // Count bookings per hour
        $bookings = Booking::where('event_date', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->get();

        $hourBookingCount = [];
        foreach ($bookings as $booking) {
            $start = (int) substr($booking->start_time, 0, 2);
            $end = (int) substr($booking->end_time, 0, 2);
            for ($h = $start; $h < $end; $h++) {
                $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                $hourBookingCount[$hourStr] = ($hourBookingCount[$hourStr] ?? 0) + 1;
            }
        }

        // Hour is available if team has capacity left and it's not blocked
        $available = array_values(array_filter($allHours, function ($h) use ($hourBookingCount, $blockedHours) {
            if (in_array($h, $blockedHours)) return false;
            return ($hourBookingCount[$h] ?? 0) < self::TEAM_CAPACITY;
        }));

        return response()->json($available);
    }

    public function submitBooking(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'event_date' => 'required|date|after:today',
            'start_time' => 'required|string|size:5',
            'end_time' => 'required|string|size:5',
            'service_type' => 'required|string',
            'message' => 'nullable|string',
            'gdpr_consent' => 'required|accepted',
        ], [
            'gdpr_consent.required' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
            'gdpr_consent.accepted' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
        ]);

        // Validate start < end and within working hours
        $startHour = (int) substr($validated['start_time'], 0, 2);
        $endHour = (int) substr($validated['end_time'], 0, 2);

        if ($startHour >= $endHour || $startHour < self::getWorkStart() || $endHour > self::getWorkEnd()) {
            return back()->withErrors(['start_time' => 'Невалиден часови диапазон.'])->withInput();
        }

        // Check date is not fully blocked
        $blockedDate = BlockedDate::where('date', $validated['event_date'])->first();
        if ($blockedDate && $blockedDate->isFullDayBlocked()) {
            return back()->withErrors(['event_date' => 'Тази дата не е налична.'])->withInput();
        }

        // Check requested hours are available
        $requestedHours = [];
        for ($h = $startHour; $h < $endHour; $h++) {
            $requestedHours[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        // Check against blocked hours
        if ($blockedDate && !$blockedDate->isFullDayBlocked()) {
            $overlap = array_intersect($requestedHours, $blockedDate->blocked_hours);
            if (!empty($overlap)) {
                return back()->withErrors(['start_time' => 'Някои от избраните часове са блокирани.'])->withInput();
            }
        }

        // Check team capacity for requested hours
        $existingBookings = Booking::where('event_date', $validated['event_date'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->get();

        $hourBookingCount = [];
        foreach ($existingBookings as $existing) {
            $exStart = (int) substr($existing->start_time, 0, 2);
            $exEnd = (int) substr($existing->end_time, 0, 2);
            for ($h = $exStart; $h < $exEnd; $h++) {
                $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                $hourBookingCount[$hourStr] = ($hourBookingCount[$hourStr] ?? 0) + 1;
            }
        }

        foreach ($requestedHours as $rh) {
            if (($hourBookingCount[$rh] ?? 0) >= self::TEAM_CAPACITY) {
                return back()->withErrors(['start_time' => 'Няма свободен фотограф за някои от избраните часове.'])->withInput();
            }
        }

        $booking = Booking::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'service_type' => $validated['service_type'],
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        Log::info("New Booking Request", $validated);

        try {
            Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewBookingNotification($booking));
        } catch (\Exception $e) {
            Log::error("Failed to send booking email: " . $e->getMessage());
        }

        return back()->with('success', 'Вашата заявка за резервация е приета! Ще се свържем с вас за потвърждение.');
    }
}
