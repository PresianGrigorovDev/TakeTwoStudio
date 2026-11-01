<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\TeamCalendar;
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
        $today = now()->toDateString();

        // Busy people per hour come from the team calendar (engagements, personal busy
        // time, studio closed); capacity is the number of active team members.
        $entries = TeamCalendar::entries($startOfMonth->toDateString(), $endOfMonth->toDateString());

        $availability = [];
        foreach (TeamCalendar::days($startOfMonth, $endOfMonth) as $date) {
            $availability[$date] = $date < $today ? 'past' : TeamCalendar::dayStatus($date, $entries);
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

        return response()->json(TeamCalendar::availableHours(Carbon::parse($request->input('date'))->toDateString()));
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

        // Every requested hour needs at least one free person (closed studio, busy
        // people and other engagements are taken from the team calendar).
        $requestedHours = [];
        for ($h = $startHour; $h < $endHour; $h++) {
            $requestedHours[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        $freeHours = TeamCalendar::availableHours(Carbon::parse($validated['event_date'])->toDateString());
        if (array_diff($requestedHours, $freeHours)) {
            return back()->withErrors(['start_time' => 'Няма свободен фотограф за някои от избраните часове.'])->withInput();
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
