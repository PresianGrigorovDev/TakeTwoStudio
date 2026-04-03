<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\PageController::class, 'home']);

Route::get('/weddings', [App\Http\Controllers\PageController::class, 'weddings']);

Route::get('/proms', [App\Http\Controllers\PageController::class, 'proms']);

Route::get('/baptism', [App\Http\Controllers\PageController::class, 'baptism']);

Route::get('/commercial', [App\Http\Controllers\PageController::class, 'commercial']);
Route::get('/graduation', [App\Http\Controllers\PageController::class, 'graduation']);

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('filament:cache-components');
    return 'Cache cleared!';
});

Route::get('/seed-ads', function () {
    (new \Database\Seeders\CommercialPortfolioPhotoSeeder())->run();
    (new \Database\Seeders\GraduationContentSeeder())->run();
    return 'Seed complete – ads: ' . \App\Models\CommercialPortfolioPhoto::count()
         . ', grad FAQ: ' . \App\Models\GraduationFaq::count()
         . ', grad packages: ' . \App\Models\GraduationPackage::count();
});

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder']);
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

