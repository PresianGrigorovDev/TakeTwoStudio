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
            'date' => 'nullable|string|max:255',
            'orderType' => 'nullable|string'
        ]);

        $typeOfService = $validated['orderType'] ?? 'Поръчка от калкулатор';
        if ($request->has('school')) {
            $typeOfService = "Абитуриентски Бал";
        } elseif ($request->has('date')) {
            $typeOfService = "Свето Кръщение";
        } elseif ($request->has('wedding_date')) { 
             $typeOfService = "Сватба";
        }

        // Save to Database
        $order = \App\Models\Order::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            // 'email' => $validated['email'] ?? null, // Email is not in calculator forms yet
            'service_type' => $typeOfService,
            'price' => $validated['final_price'],
            'details' => $validated['details'],
            'status' => 'new'
        ]);
        
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
        $order = \App\Models\Order::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'service_type' => $orderType,
            'details' => $validated['message'],
            'status' => 'new'
        ]);

        // Log the contact inquiry
        Log::info("New Contact Inquiry: $orderType", $validated);

        try {
            Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewOrderNotification($order));
        } catch (\Exception $e) {
            Log::error("Failed to send contact inquiry email: " . $e->getMessage());
        }

        return back()->with('success', 'Благодарим ви! Съобщението е изпратено.');
    }
}
