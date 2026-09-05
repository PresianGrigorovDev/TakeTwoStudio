<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\PageController::class, 'home']);

Route::get('/weddings', [App\Http\Controllers\PageController::class, 'weddings']);
Route::get('/svatbi/{slug}', [App\Http\Controllers\WeddingStoryController::class, 'show'])->name('weddings.story');

Route::get('/proms', [App\Http\Controllers\PageController::class, 'proms']);

Route::get('/baptism', [App\Http\Controllers\PageController::class, 'baptism']);

Route::get('/commercial', [App\Http\Controllers\PageController::class, 'commercial']);
Route::redirect('/graduation', '/proms', 301);

Route::get('/family', [App\Http\Controllers\PageController::class, 'family']);
Route::get('/portrait', [App\Http\Controllers\PageController::class, 'portrait']);
Route::get('/automotive', [App\Http\Controllers\PageController::class, 'automotive']);
Route::get('/architectural', [App\Http\Controllers\PageController::class, 'architectural']);
Route::get('/events', [App\Http\Controllers\PageController::class, 'events']);

Route::get('/ceni', [App\Http\Controllers\SitePageController::class, 'prices'])->name('pages.prices');
Route::get('/za-nas', [App\Http\Controllers\SitePageController::class, 'about'])->name('pages.about');
Route::get('/kontakti', [App\Http\Controllers\SitePageController::class, 'contact'])->name('pages.contact');
Route::get('/abiturientski-bal-varna', [App\Http\Controllers\SitePageController::class, 'promGuide'])->name('pages.prom-guide');

Route::get('/blog', [App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [App\Http\Controllers\BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [App\Http\Controllers\SitemapController::class, 'pages']);
Route::get('/sitemap-blog.xml', [App\Http\Controllers\SitemapController::class, 'blog']);
Route::get('/sitemap-images.xml', [App\Http\Controllers\SitemapController::class, 'images']);

// IndexNow key verification file (Bing). Only exists when INDEXNOW_KEY is configured.
if ($indexNowKey = \App\Support\Seo\IndexNow::key()) {
    Route::get('/'.$indexNowKey.'.txt', fn () => response($indexNowKey, 200, ['Content-Type' => 'text/plain']));
}

Route::get('/llms.txt', [App\Http\Controllers\LLMController::class, 'index']);
Route::get('/llms-full.txt', [App\Http\Controllers\LLMController::class, 'full']);

Route::get('/privacy', fn () => app(App\Http\Controllers\LegalPageController::class)->show('privacy'))->name('legal.privacy');
Route::get('/terms', fn () => app(App\Http\Controllers\LegalPageController::class)->show('terms'))->name('legal.terms');
Route::get('/cookies', fn () => app(App\Http\Controllers\LegalPageController::class)->show('cookies'))->name('legal.cookies');

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder'])->middleware('throttle:6,1');
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact'])->middleware('throttle:6,1');
Route::post('/api/validate-promo-code', [App\Http\Controllers\OrderController::class, 'validatePromoCode'])->name('promo.validate')->middleware('throttle:20,1');

Route::get('/booking', [App\Http\Controllers\BookingController::class, 'showCalendar']);
Route::post('/submit-booking', [App\Http\Controllers\BookingController::class, 'submitBooking'])->middleware('throttle:6,1');
Route::get('/api/booking-availability', [App\Http\Controllers\BookingController::class, 'getAvailability'])->middleware('throttle:60,1');
Route::get('/api/booking-hours', [App\Http\Controllers\BookingController::class, 'getAvailableHours'])->middleware('throttle:60,1');

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
