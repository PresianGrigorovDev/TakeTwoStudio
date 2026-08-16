<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::get('/', [App\Http\Controllers\PageController::class, 'home']);

Route::get('/weddings', [App\Http\Controllers\PageController::class, 'weddings']);

Route::get('/proms', [App\Http\Controllers\PageController::class, 'proms']);

Route::get('/baptism', [App\Http\Controllers\PageController::class, 'baptism']);

Route::get('/commercial', [App\Http\Controllers\PageController::class, 'commercial']);
Route::redirect('/graduation', '/proms', 301);

Route::get('/family', [App\Http\Controllers\PageController::class, 'family']);
Route::get('/portrait', [App\Http\Controllers\PageController::class, 'portrait']);
Route::get('/automotive', [App\Http\Controllers\PageController::class, 'automotive']);
Route::get('/architectural', [App\Http\Controllers\PageController::class, 'architectural']);
Route::get('/events', [App\Http\Controllers\PageController::class, 'events']);

Route::get('/blog', [App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [App\Http\Controllers\BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);

Route::get('/llms.txt', [App\Http\Controllers\LLMController::class, 'index']);
Route::get('/llms-full.txt', [App\Http\Controllers\LLMController::class, 'full']);

Route::get('/privacy', fn () => app(App\Http\Controllers\LegalPageController::class)->show('privacy'))->name('legal.privacy');
Route::get('/terms', fn () => app(App\Http\Controllers\LegalPageController::class)->show('terms'))->name('legal.terms');
Route::get('/cookies', fn () => app(App\Http\Controllers\LegalPageController::class)->show('cookies'))->name('legal.cookies');

Route::get('/test-email-send', function () {
    $inquiry = \App\Models\Inquiry::create([
        'customer_name'  => 'Тестов Клиент (Автоматичен Тест)',
        'customer_phone' => '0888 123 456',
        'customer_email' => 'test@taketwostudio.bg',
        'service_type'   => 'Тест на имейл функцията',
        'message'        => 'Здравейте! Това е автоматично тестово запитване за проверка на изпращането на имейли.',
        'status'         => 'new',
    ]);

    try {
        \Illuminate\Support\Facades\Mail::to(config('mail.admin_email'))->send(new \App\Mail\NewInquiryNotification($inquiry));
        return response()->json([
            'success' => true,
            'message' => 'Тестовото запитване е записано в базата и имейлът бе изпратен успешно до: ' . config('mail.admin_email'),
            'inquiry_id' => $inquiry->id,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Запитването бе записано в базата данни (ID: ' . $inquiry->id . '), но възникна грешка при изпращането на имейл!',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/clear-cache', function () {
    if (Auth::check() && Auth::user()->isAdmin()) {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('filament:cache-components');
        return redirect('/')->with('success', 'Cache cleared!');
    }
    abort(403);
});

Route::get('/seed-all', [App\Http\Controllers\SeedController::class, 'run']);

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder'])->middleware('throttle:6,1');
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact'])->middleware('throttle:6,1');
Route::post('/api/validate-promo-code', [App\Http\Controllers\OrderController::class, 'validatePromoCode'])->name('promo.validate')->middleware('throttle:20,1');

Route::get('/booking', [App\Http\Controllers\BookingController::class, 'showCalendar']);
Route::post('/submit-booking', [App\Http\Controllers\BookingController::class, 'submitBooking'])->middleware('throttle:6,1');
Route::get('/api/booking-availability', [App\Http\Controllers\BookingController::class, 'getAvailability']);
Route::get('/api/booking-hours', [App\Http\Controllers\BookingController::class, 'getAvailableHours']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

if (app()->environment('local')) {
    Route::get('/force-login', function () {
        $user = User::updateOrCreate(
            ['email' => 'presiangrigorovdev@gmail.com'],
            [
                'name' => 'Presian',
                'password' => Hash::make('12345678'),
                'is_admin' => true,
            ]
        );
        
        Auth::login($user);
        return redirect('/admin');
    });
}
