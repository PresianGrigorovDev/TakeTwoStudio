<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\LLMController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SitePageController;
use App\Http\Controllers\WeddingStoryController;
use App\Support\Seo\IndexNow;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', [PageController::class, 'home']);

Route::get('/weddings', [PageController::class, 'weddings']);
Route::get('/svatbi/{slug}', [WeddingStoryController::class, 'show'])->name('weddings.story');

Route::get('/proms', [PageController::class, 'proms']);

Route::get('/baptism', [PageController::class, 'baptism']);

Route::get('/commercial', [PageController::class, 'commercial']);
Route::redirect('/graduation', '/proms', 301);

Route::get('/family', [PageController::class, 'family']);
Route::get('/portrait', [PageController::class, 'portrait']);
Route::get('/automotive', [PageController::class, 'automotive']);
Route::get('/architectural', [PageController::class, 'architectural']);
Route::get('/events', [PageController::class, 'events']);

Route::get('/ceni', [SitePageController::class, 'prices'])->name('pages.prices');
Route::get('/za-nas', [SitePageController::class, 'about'])->name('pages.about');
Route::get('/kontakti', [SitePageController::class, 'contact'])->name('pages.contact');
Route::get('/abiturientski-bal-varna', [SitePageController::class, 'promGuide'])->name('pages.prom-guide');

// QR игра (/igra?target=prom|wedding&loc=<стикер>) – noindex, без nav/sitemap, без сесия/бисквитки.
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
])->group(function () {
    Route::get('/igra', [GameController::class, 'show'])->name('game.show');
    Route::post('/api/igra/event', [GameController::class, 'event'])->name('game.event')->middleware('throttle:120,1');
});

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog']);
Route::get('/sitemap-images.xml', [SitemapController::class, 'images']);

// IndexNow key verification file (Bing). Only exists when INDEXNOW_KEY is configured.
if ($indexNowKey = IndexNow::key()) {
    Route::get('/'.$indexNowKey.'.txt', fn () => response($indexNowKey, 200, ['Content-Type' => 'text/plain']));
}

Route::get('/llms.txt', [LLMController::class, 'index']);
Route::get('/llms-full.txt', [LLMController::class, 'full']);

Route::get('/privacy', fn () => app(LegalPageController::class)->show('privacy'))->name('legal.privacy');
Route::get('/terms', fn () => app(LegalPageController::class)->show('terms'))->name('legal.terms');
Route::get('/cookies', fn () => app(LegalPageController::class)->show('cookies'))->name('legal.cookies');

Route::post('/submit-order', [OrderController::class, 'submitOrder'])->middleware('throttle:6,1');
Route::post('/submit-contact', [OrderController::class, 'submitContact'])->middleware('throttle:6,1');
Route::post('/api/validate-promo-code', [OrderController::class, 'validatePromoCode'])->name('promo.validate')->middleware('throttle:20,1');

Route::get('/booking', [BookingController::class, 'showCalendar']);
Route::post('/submit-booking', [BookingController::class, 'submitBooking'])->middleware('throttle:6,1');
Route::get('/api/booking-availability', [BookingController::class, 'getAvailability'])->middleware('throttle:60,1');
Route::get('/api/booking-hours', [BookingController::class, 'getAvailableHours'])->middleware('throttle:60,1');

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
