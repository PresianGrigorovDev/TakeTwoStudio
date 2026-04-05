<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function submitOrder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'final_price' => 'required|numeric',
            'details' => 'required|string',
            'school' => 'nullable|string|max:255',
            'date' => 'nullable|date|max:255',
            'graduation_date' => 'nullable|date',
            'graduation_start_time' => 'nullable|string|size:5',
            'graduation_end_time' => 'nullable|string|size:5',
            'orderType' => 'nullable|string'
        ]);

        $orderType = $validated['orderType'] ?? null;
        $eventDate = null;

        $typeMap = [
            'Wedding' => 'Сватба',
            'Prom' => 'Абитуриентски Бал',
            'Baptism' => 'Свето Кръщене',
            'Graduation' => 'Изпращане',
            'Commercial' => 'Реклама и Бизнес',
        ];

        $typeOfService = $typeMap[$orderType] ?? 'Поръчка от калкулатор';

        // Extract event date from the appropriate field
        if (!empty($validated['graduation_date'])) {
            $eventDate = $validated['graduation_date'];
        } elseif (!empty($validated['date'])) {
            $eventDate = $validated['date'];
        }

        $startTime = $validated['graduation_start_time'] ?? null;
        $endTime = $validated['graduation_end_time'] ?? null;

        // Save to Database
        $order = \App\Models\Order::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'service_type' => $typeOfService,
            'price' => $validated['final_price'],
            'details' => $validated['details'],
            'status' => 'new',
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Auto-create a booking if we have an event date
        if ($eventDate) {
            $workStart = BookingController::WORK_START;
            $workEnd = BookingController::WORK_END;

            \App\Models\Booking::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'event_date' => $eventDate,
                'start_time' => $startTime ?: str_pad($workStart, 2, '0', STR_PAD_LEFT) . ':00',
                'end_time' => $endTime ?: str_pad($workEnd, 2, '0', STR_PAD_LEFT) . ':00',
                'service_type' => $typeOfService,
                'message' => $validated['details'],
                'status' => 'pending',
                'order_id' => $order->id,
            ]);
        }

        // Log the order
        Log::info("New Order: $typeOfService", $validated);

        try {
            Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewOrderNotification($order));
        } catch (\Exception $e) {
            Log::error("Failed to send order email: " . $e->getMessage());
        }

        return back()->with('success', 'Вашето запитване е прието успешно! Ще се свържем с вас скоро.');
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
            'orderType' => 'nullable|string' // Commercial page uses this
        ]);

        $orderType = $validated['orderType'] ?? 'General Inquiry';

        // Save to Database
        $inquiry = \App\Models\Inquiry::create([
            'customer_name' => $validated['name'],
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'],
            'service_type' => $orderType,
            'message' => $validated['message'],
            'status' => 'new'
        ]);

        // Log the contact inquiry
        Log::info("New Contact Inquiry: $orderType", $validated);

        try {
            Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewInquiryNotification($inquiry));
        } catch (\Exception $e) {
            Log::error("Failed to send contact inquiry email: " . $e->getMessage());
        }

        return back()->with('success', 'Благодарим ви! Съобщението е изпратено.');
    }
}
