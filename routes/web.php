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

    // Family Packages
    if (\App\Models\FamilyPackage::count() === 0) {
        $familyPackages = [
            ['name' => 'Мини Сесия', 'price' => 154.51, 'price_eur' => 79, 'description' => '30 мин. фотосесия, 1 локация', 'features' => ['30 минути фотосесия', '1 локация', '15 обработени снимки', 'Онлайн галерия за сваляне'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
            ['name' => 'Стандарт', 'price' => 291.32, 'price_eur' => 149, 'description' => '1 час фотосесия, до 2 локации', 'features' => ['1 час фотосесия', 'До 2 локации', '30 обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
            ['name' => 'Премиум', 'price' => 447.82, 'price_eur' => 229, 'description' => '1.5 часа, до 3 локации + отпечатъци', 'features' => ['1.5 часа фотосесия', 'До 3 локации', '50 обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш', '5 отпечатъка 20x30 см'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
        ];
        foreach ($familyPackages as $pkg) {
            \App\Models\FamilyPackage::create($pkg);
        }
    }

    // Portrait Packages
    if (\App\Models\PortraitPackage::count() === 0) {
        $portraitPackages = [
            ['name' => 'Headshot', 'price' => 134.92, 'price_eur' => 69, 'description' => '20 мин. в студио, за LinkedIn/CV', 'features' => ['20 минути в студио', '1 фон', '5 обработени снимки', 'Кожен ретуш', 'Идеално за LinkedIn и CV'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
            ['name' => 'Стандарт', 'price' => 252.11, 'price_eur' => 129, 'description' => '45 мин., 2 фона или локация', 'features' => ['45 минути фотосесия', '2 фона или 1 локация на открито', '15 обработени снимки', 'Пълен кожен ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
            ['name' => 'Артистичен', 'price' => 427.93, 'price_eur' => 219, 'description' => '1.5 часа, студио + локация, креативен', 'features' => ['1.5 часа фотосесия', 'Студио + локация на открито', '25 обработени снимки', 'Креативно осветление и концепция', 'Пълен ретуш и цветова корекция', 'Онлайн галерия за сваляне'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
        ];
        foreach ($portraitPackages as $pkg) {
            \App\Models\PortraitPackage::create($pkg);
        }
    }

    // Automotive Packages
    if (\App\Models\AutomotivePackage::count() === 0) {
        $automotivePackages = [
            ['name' => 'Обява', 'price' => 173.96, 'price_eur' => 89, 'description' => '30 мин., за обяви за продажба', 'features' => ['30 минути заснемане', '1 локация', '10 обработени снимки', 'Екстериор + интериор', 'Цветова корекция'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
            ['name' => 'Шоурум', 'price' => 349.89, 'price_eur' => 179, 'description' => '1 час, детайли и интериор', 'features' => ['1 час заснемане', 'До 2 локации', '20 обработени снимки', 'Екстериор, интериор и детайли', 'Професионален ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
            ['name' => 'Кинематографичен', 'price' => 584.75, 'price_eur' => 299, 'description' => '2 часа, снимки + видео клип', 'features' => ['2 часа заснемане', 'Локация по избор', '30 обработени снимки', 'Видео клип 30 секунди', 'Динамични и статични кадри', 'Пълен ретуш и цветова корекция'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
        ];
        foreach ($automotivePackages as $pkg) {
            \App\Models\AutomotivePackage::create($pkg);
        }
    }

    // Architectural Packages
    if (\App\Models\ArchitecturalPackage::count() === 0) {
        $architecturalPackages = [
            ['name' => 'Имот', 'price' => 252.11, 'price_eur' => 129, 'description' => 'До 5 помещения, за обяви', 'features' => ['До 5 помещения', '15 HDR снимки', 'Широкоъгълна оптика', 'Цветова корекция', 'Идеално за обяви за продажба/наем'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
            ['name' => 'Бизнес', 'price' => 486.70, 'price_eur' => 249, 'description' => 'До 10 помещения + дрон', 'features' => ['До 10 помещения + екстериор', '30 HDR снимки', 'Широкоъгълна оптика', 'Дрон кадри', 'Професионален ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
            ['name' => 'Луксозен', 'price' => 780.27, 'price_eur' => 399, 'description' => 'Неограничени помещения + 360°', 'features' => ['Неограничен брой помещения', '50+ HDR снимки', 'Широкоъгълна оптика', 'Дрон кадри', 'Виртуална обиколка 360°', 'Пълен ретуш и цветова корекция'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
        ];
        foreach ($architecturalPackages as $pkg) {
            \App\Models\ArchitecturalPackage::create($pkg);
        }
    }

    // Event Packages
    if (\App\Models\EventPackage::count() === 0) {
        $eventPackages = [
            ['name' => 'Стандарт', 'price' => 389.02, 'price_eur' => 199, 'description' => 'До 2 часа заснемане', 'features' => ['До 2 часа заснемане', '50 обработени снимки', 'Онлайн галерия за сваляне', 'Предаване до 14 дни'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
            ['name' => 'Разширен', 'price' => 682.23, 'price_eur' => 349, 'description' => 'До 4 часа + видео 1 мин.', 'features' => ['До 4 часа заснемане', '100 обработени снимки', 'Кратко видео 1 минута', 'Онлайн галерия за сваляне', 'Предаване до 10 дни'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
            ['name' => 'Цялостен', 'price' => 975.79, 'price_eur' => 499, 'description' => 'Целодневно + видео 2-3 мин.', 'features' => ['Целодневно заснемане (до 8 часа)', '200+ обработени снимки', 'Видео клип 2-3 минути', 'Онлайн галерия за сваляне', 'Експресно предаване до 48 часа'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
        ];
        foreach ($eventPackages as $pkg) {
            \App\Models\EventPackage::create($pkg);
        }
    }

    return 'Seed complete – ads: ' . \App\Models\CommercialPortfolioPhoto::count()
         . ', grad FAQ: ' . \App\Models\GraduationFaq::count()
         . ', grad packages: ' . \App\Models\GraduationPackage::count()
         . ', prom FAQ: ' . \App\Models\PromFaq::count()
         . ', prom packages: ' . \App\Models\PromPackage::count()
         . ', baptism FAQ: ' . \App\Models\BaptismFaq::count()
         . ', baptism packages: ' . \App\Models\BaptismPackage::count()
         . ', family packages: ' . \App\Models\FamilyPackage::count()
         . ', portrait packages: ' . \App\Models\PortraitPackage::count()
         . ', automotive packages: ' . \App\Models\AutomotivePackage::count()
         . ', architectural packages: ' . \App\Models\ArchitecturalPackage::count()
         . ', event packages: ' . \App\Models\EventPackage::count();

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

