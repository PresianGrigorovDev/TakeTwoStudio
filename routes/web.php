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

    // Prom FAQs
    if (\App\Models\PromFaq::count() === 0) {
        $promFaqs = [
            ['question' => 'Колко струва заснемането на абитуриентски бал?', 'answer' => 'Цените ни започват от €103 на ученик и включват фотосесия, видеозаснемане на каненето и бала. Използвайте нашия калкулатор по-горе за точна сума.', 'sort_order' => 1],
            ['question' => 'Кога трябва да запазим дата за бала?', 'answer' => 'Препоръчваме да запазите дата минимум 3–4 месеца предварително. Абитуриентският сезон е натоварен и местата се запълват бързо.', 'sort_order' => 2],
            ['question' => 'Предлагате ли дрон кадри за бала?', 'answer' => 'Да, дрон заснемането е налично като екстра към всеки пакет. Въздушните кадри добавят кинематографична визия към видеото от вашия специален ден.', 'sort_order' => 3],
            ['question' => 'Кога получаваме готовите снимки и видео?', 'answer' => 'Обработените материали се предават до 30 работни дни след събитието. При желание за по-бърза доставка предлагаме експресна обработка.', 'sort_order' => 4],
        ];
        foreach ($promFaqs as $faq) {
            \App\Models\PromFaq::create($faq);
        }
    }

    return 'Seed complete – ads: ' . \App\Models\CommercialPortfolioPhoto::count()
         . ', grad FAQ: ' . \App\Models\GraduationFaq::count()
         . ', grad packages: ' . \App\Models\GraduationPackage::count()
         . ', prom FAQ: ' . \App\Models\PromFaq::count();
});

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder']);
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

