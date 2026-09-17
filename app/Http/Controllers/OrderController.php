<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\PromoCode;
use App\Support\GameVoucher;

class OrderController extends Controller
{
    public function submitOrder(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'phone'                 => 'required|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'final_price'           => 'required|numeric',
            'details'               => 'required|string',
            'school'                => 'nullable|string|max:255',
            'date'                  => 'nullable|date|max:255',
            'graduation_date'       => 'nullable|date',
            'graduation_start_time' => 'nullable|string|size:5',
            'graduation_end_time'   => 'nullable|string|size:5',
            'orderType'             => 'nullable|string',
            'promo_code'            => 'nullable|string|max:50',
            'gdpr_consent'          => 'required|accepted',
        ], [
            'gdpr_consent.required' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
            'gdpr_consent.accepted' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
        ]);

        $orderType = $validated['orderType'] ?? null;
        $eventDate = null;

        $typeMap = [
            'Wedding'    => 'Сватба',
            'Prom'       => 'Абитуриентски Бал',
            'Baptism'    => 'Свето Кръщене',
            'Graduation' => 'Изпращане',
            'Commercial' => 'Реклама и Бизнес',
        ];

        $typeOfService = $typeMap[$orderType] ?? 'Поръчка от калкулатор';

        // Extract event date from the appropriate field
        if (! empty($validated['graduation_date'])) {
            $eventDate = $validated['graduation_date'];
        } elseif (! empty($validated['date'])) {
            $eventDate = $validated['date'];
        }

        $startTime = $validated['graduation_start_time'] ?? null;
        $endTime   = $validated['graduation_end_time'] ?? null;

        // --- Promo code handling ---
        $promoCodeId     = null;
        $promoCodeStr    = null;
        $discountAmount  = null;
        $finalPrice      = (float) $validated['final_price'];

        // NOTE: the calculators already apply the discount in the browser (applyPromoDiscount) and post the
        // discounted total as final_price, so the server only RECORDS the discount instead of subtracting it again.
        if (! empty($validated['promo_code'])) {
            $code      = strtoupper(trim($validated['promo_code']));
            $promoCode = PromoCode::where('code', $code)->first();

            if ($promoCode && $promoCode->isValid()) {
                $discountAmount = GameVoucher::discountFromDiscountedPrice($finalPrice, $promoCode->discount_type, (float) $promoCode->discount_value);
                $promoCodeId    = $promoCode->id;
                $promoCodeStr   = $promoCode->code;

                // Increment usage count
                $promoCode->increment('uses_count');
            } elseif (($voucher = GameVoucher::find($code)) !== null) {
                // Voucher from the QR game (/igra): one-time use, marked as redeemed here.
                $percent        = GameVoucher::percentOf($voucher);
                $discountAmount = GameVoucher::discountFromDiscountedPrice($finalPrice, 'percent', $percent);
                $promoCodeStr   = $code;

                GameVoucher::redeem($voucher);
            }
        }

        // Save to Database
        $order = \App\Models\Order::create([
            'name'            => $validated['name'],
            'phone'           => $validated['phone'],
            'email'           => $validated['email'] ?? null,
            'service_type'    => $typeOfService,
            'price'           => $finalPrice,
            'details'         => $validated['details'],
            'status'          => 'new',
            'event_date'      => $eventDate,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'promo_code_id'   => $promoCodeId,
            'promo_code'      => $promoCodeStr,
            'discount_amount' => $discountAmount,
        ]);

        // Auto-create a booking if we have an event date
        if ($eventDate) {
            $workStart = BookingController::WORK_START;
            $workEnd   = BookingController::WORK_END;

            \App\Models\Booking::create([
                'name'         => $validated['name'],
                'phone'        => $validated['phone'],
                'event_date'   => $eventDate,
                'start_time'   => $startTime ?: str_pad($workStart, 2, '0', STR_PAD_LEFT) . ':00',
                'end_time'     => $endTime ?: str_pad($workEnd, 2, '0', STR_PAD_LEFT) . ':00',
                'service_type' => $typeOfService,
                'message'      => $validated['details'],
                'status'       => 'pending',
                'order_id'     => $order->id,
            ]);
        }

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
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'message'     => 'nullable|string',
            'orderType'   => 'nullable|string',
            'gdpr_consent' => 'required|accepted',
        ], [
            'gdpr_consent.required' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
            'gdpr_consent.accepted' => 'Трябва да приемете Политиката за поверителност и Общите условия.',
        ]);

        $orderType = $validated['orderType'] ?? 'Бърза Оферта';

        // Save to Database
        $inquiry = \App\Models\Inquiry::create([
            'customer_name'  => $validated['name'],
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'] ?? 'без имейл',
            'service_type'   => $orderType,
            'message'        => $validated['message'] ?? 'Бързо запитване от Hero модален прозорец.',
            'status'         => 'new',
        ]);

        Log::info("New Contact Inquiry: $orderType", $validated);

        try {
            Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewInquiryNotification($inquiry));
        } catch (\Exception $e) {
            Log::error("Failed to send contact inquiry email: " . $e->getMessage());
        }

        return back()->with('success', 'Благодарим ви! Съобщението е изпратено.');
    }

    /**
     * AJAX endpoint: validate a promo code and return discount info.
     */
    public function validatePromoCode(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));

        if (empty($code)) {
            return response()->json(['valid' => false, 'message' => 'Моля, въведете промо код.']);
        }

        $promoCode = PromoCode::where('code', $code)->first();

        if (! $promoCode) {
            // Voucher from the QR game (/igra): valid 72h after it was issued, until redeemed.
            if (GameVoucher::isGameCode($code)) {
                $voucher = GameVoucher::find($code);

                if (! $voucher) {
                    return response()->json(['valid' => false, 'message' => 'Кодът от играта е изтекъл, вече е използван или не е разпознат.']);
                }

                $percent = GameVoucher::percentOf($voucher);

                return response()->json([
                    'valid'          => true,
                    'discount_type'  => 'percent',
                    'discount_value' => (float) $percent,
                    'message'        => "Кодът от играта е приложен! Намаление: {$percent}%",
                ]);
            }

            return response()->json(['valid' => false, 'message' => 'Невалиден промо код.']);
        }

        if (! $promoCode->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Промо кодът е изтекъл или е достигнал максималния брой ползвания.']);
        }

        $label = $promoCode->discount_type === 'percent'
            ? number_format((float) $promoCode->discount_value, 0) . '%'
            : '€' . number_format((float) $promoCode->discount_value, 0);

        return response()->json([
            'valid'          => true,
            'discount_type'  => $promoCode->discount_type,
            'discount_value' => (float) $promoCode->discount_value,
            'message'        => "Промо кодът е приложен! Намаление: {$label}",
        ]);
    }
}
