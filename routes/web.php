<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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
    return redirect('/')->with('success', 'Cache cleared!');
});

Route::get('/seed-all', function () {
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

    // Baptism FAQs
    if (\App\Models\BaptismFaq::count() === 0) {
        $baptismFaqs = [
            ['question' => 'Колко време трае самото кръщене?', 'answer' => 'Самият църковен ритуал обикновено трае около 40-50 минути. Ние винаги сме там 15-20 минути по-рано, за да снимаме детайлите и гостите. След ритуала отделяме време за семейна фотосесия пред църквата. Общо ангажиментът е около 1.5 часа (за пакет "Само Църква").', 'sort_order' => 1],
            ['question' => 'Кога получаваме готовите снимки и видео?', 'answer' => 'Стандартният срок за предаване на обработените кадри и видеото е до 30 работни дни. Ако имате нужда от материалите по-бързо, предлагаме услуга "Експресна обработка" (до 3 дни).', 'sort_order' => 2],
            ['question' => 'Снимате ли в ресторанта?', 'answer' => 'Да, предлагаме разширен пакет, който включва и заснемане на тържеството в ресторанта (посрещане, разрязване на питата, торта и весели моменти с гостите).', 'sort_order' => 3],
        ];
        foreach ($baptismFaqs as $faq) {
            \App\Models\BaptismFaq::create($faq);
        }
    }

    return 'Seed complete – ads: ' . \App\Models\CommercialPortfolioPhoto::count()
         . ', grad FAQ: ' . \App\Models\GraduationFaq::count()
         . ', grad packages: ' . \App\Models\GraduationPackage::count()
         . ', prom FAQ: ' . \App\Models\PromFaq::count()
         . ', prom packages: ' . \App\Models\PromPackage::count()
         . ', baptism FAQ: ' . \App\Models\BaptismFaq::count()
         . ', baptism packages: ' . \App\Models\BaptismPackage::count();
         
});

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder']);
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact']);

Route::get('/booking', [App\Http\Controllers\BookingController::class, 'showCalendar']);
Route::post('/submit-booking', [App\Http\Controllers\BookingController::class, 'submitBooking']);
Route::get('/api/booking-availability', [App\Http\Controllers\BookingController::class, 'getAvailability']);
Route::get('/api/booking-hours', [App\Http\Controllers\BookingController::class, 'getAvailableHours']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

