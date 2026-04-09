<?php

namespace App\Http\Controllers;

class SeedController extends Controller
{
    public function run()
    {
        (new \Database\Seeders\CommercialPortfolioPhotoSeeder())->run();
        (new \Database\Seeders\GraduationContentSeeder())->run();

        $this->seedPromFaqs();
        $this->seedBaptismFaqs();
        $this->seedWeddingPackages();
        $this->seedPromPackages();
        $this->seedBaptismPackages();
        $this->seedCommercialPackages();
        $this->seedFamilyPackages();
        $this->seedPortraitPackages();
        $this->seedAutomotivePackages();
        $this->seedArchitecturalPackages();
        $this->seedEventPackages();
        $this->seedExtras();
        $this->seedPageContents();

        return 'Seed complete'
            . ' – wedding pkg: ' . \App\Models\WeddingPackage::count()
            . ', prom pkg: ' . \App\Models\PromPackage::count()
            . ', baptism pkg: ' . \App\Models\BaptismPackage::count()
            . ', commercial pkg: ' . \App\Models\CommercialPackage::count()
            . ', family pkg: ' . \App\Models\FamilyPackage::count()
            . ', portrait pkg: ' . \App\Models\PortraitPackage::count()
            . ', automotive pkg: ' . \App\Models\AutomotivePackage::count()
            . ', architectural pkg: ' . \App\Models\ArchitecturalPackage::count()
            . ', event pkg: ' . \App\Models\EventPackage::count()
            . ', service extras: ' . \App\Models\ServiceExtra::count();
    }

    private function seedPromFaqs()
    {
        if (\App\Models\PromFaq::count() === 0) {
            foreach ([
                ['question' => 'Колко струва заснемането на абитуриентски бал?', 'answer' => 'Цените ни започват от €103 на ученик и включват фотосесия, видеозаснемане на каненето и бала. Използвайте нашия калкулатор по-горе за точна сума.', 'sort_order' => 1],
                ['question' => 'Кога трябва да запазим дата за бала?', 'answer' => 'Препоръчваме да запазите дата минимум 3–4 месеца предварително. Абитуриентският сезон е натоварен и местата се запълват бързо.', 'sort_order' => 2],
                ['question' => 'Предлагате ли дрон кадри за бала?', 'answer' => 'Да, дрон заснемането е налично като екстра към всеки пакет. Въздушните кадри добавят кинематографична визия към видеото от вашия специален ден.', 'sort_order' => 3],
                ['question' => 'Кога получаваме готовите снимки и видео?', 'answer' => 'Обработените материали се предават до 30 работни дни след събитието. При желание за по-бърза доставка предлагаме експресна обработка.', 'sort_order' => 4],
            ] as $faq) {
                \App\Models\PromFaq::create($faq);
            }
        }
    }

    private function seedBaptismFaqs()
    {
        if (\App\Models\BaptismFaq::count() === 0) {
            foreach ([
                ['question' => 'Колко време трае самото кръщене?', 'answer' => 'Самият църковен ритуал обикновено трае около 40-50 минути. Ние винаги сме там 15-20 минути по-рано, за да снимаме детайлите и гостите. След ритуала отделяме време за семейна фотосесия пред църквата. Общо ангажиментът е около 1.5 часа (за пакет "Само Църква").', 'sort_order' => 1],
                ['question' => 'Кога получаваме готовите снимки и видео?', 'answer' => 'Стандартният срок за предаване на обработените кадри и видеото е до 30 работни дни. Ако имате нужда от материалите по-бързо, предлагаме услуга "Експресна обработка" (до 3 дни).', 'sort_order' => 2],
                ['question' => 'Снимате ли в ресторанта?', 'answer' => 'Да, предлагаме разширен пакет, който включва и заснемане на тържеството в ресторанта (посрещане, разрязване на питата, торта и весели моменти с гостите).', 'sort_order' => 3],
            ] as $faq) {
                \App\Models\BaptismFaq::create($faq);
            }
        }
    }

    private function seedWeddingPackages()
    {
        if (\App\Models\WeddingPackage::count() === 0) {
            foreach ([
                ['name' => 'Видео заснемане: Един оператор', 'price' => 1740.67, 'price_eur' => 890, 'description' => 'Един видеооператор за целия сватбен ден', 'features' => ['Един видеооператор', 'Целодневно заснемане', 'Филм до 90 мин. (4K UHD)', 'Облак + флашка в кутийка', 'Цветова корекция'], 'sort_order' => 1, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Видео заснемане: Двама оператори', 'price' => 2239.39, 'price_eur' => 1145, 'description' => 'Двама оператори за мулти-ъгълно заснемане', 'features' => ['Двама видеооператори', 'Мулти-ъгълно заснемане', 'Целодневно заснемане', 'Филм до 90 мин. (4K UHD)', 'Облак + флашка в кутийка', 'Цветова корекция'], 'sort_order' => 2, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Фото заснемане: Един фотограф', 'price' => 1740.67, 'price_eur' => 890, 'description' => 'Един фотограф за целия сватбен ден', 'features' => ['Един фотограф', 'Целодневно заснемане', 'Всички обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш'], 'sort_order' => 3, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Фото заснемане: Двама фотографи', 'price' => 2239.39, 'price_eur' => 1145, 'description' => 'Двама фотографи за пълно покритие', 'features' => ['Двама фотографи', 'Мулти-ъгълно заснемане', 'Целодневно заснемане', 'Всички обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш'], 'sort_order' => 4, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\WeddingPackage::create($pkg);
            }
        }
    }

    private function seedPromPackages()
    {
        if (\App\Models\PromPackage::count() === 0) {
            foreach ([
                ['name' => 'ПАРТИ ПАКЕТ', 'price' => 195.58, 'price_eur' => 100, 'description' => 'Фотосесия и видеозаснемане на каненето и бала', 'features' => ['Фотозаснемане на бала', 'Видеозаснемане на каненето', 'Обработени снимки', 'Онлайн галерия за сваляне'], 'sort_order' => 1, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'ЛУКС ПАКЕТ', 'price' => 254.26, 'price_eur' => 130, 'description' => 'Разширен пакет с допълнителни екстри', 'features' => ['Фотозаснемане на бала', 'Видеозаснемане на каненото и бала', 'Студийна фотосесия', 'Обработени снимки', 'Кратко видео', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\PromPackage::create($pkg);
            }
        }
    }

    private function seedBaptismPackages()
    {
        if (\App\Models\BaptismPackage::count() === 0) {
            foreach ([
                ['name' => 'Фотография', 'price' => 234.70, 'price_eur' => 120, 'description' => 'Фото заснемане на кръщенето', 'features' => ['Фотозаснемане на ритуала', 'Семейна фотосесия пред църквата', 'Всички обработени снимки', 'Онлайн галерия за сваляне'], 'sort_order' => 1, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Видеозаснемане', 'price' => 254.26, 'price_eur' => 130, 'description' => 'Видео заснемане на кръщенето', 'features' => ['Видеозаснемане на ритуала', 'Монтиран филм', 'Цветова корекция', 'Облак за сваляне'], 'sort_order' => 2, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Комбо (Фото + Видео)', 'price' => 430.28, 'price_eur' => 220, 'description' => 'Фото и видео в един пакет', 'features' => ['Фотозаснемане на ритуала', 'Видеозаснемане на ритуала', 'Семейна фотосесия', 'Монтиран филм', 'Всички обработени снимки', 'Онлайн галерия за сваляне'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\BaptismPackage::create($pkg);
            }
        }
    }

    private function seedCommercialPackages()
    {
        if (\App\Models\CommercialPackage::count() === 0) {
            foreach ([
                ['name' => 'Стартов', 'price' => 291.32, 'price_eur' => 149, 'description' => '1 час, продуктова или бизнес фотосесия', 'features' => ['1 час заснемане', 'До 10 продукта или 1 локация', '15 обработени снимки', 'Цветова корекция', 'Онлайн галерия'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Бизнес', 'price' => 584.75, 'price_eur' => 299, 'description' => '3 часа, разширено заснемане', 'features' => ['3 часа заснемане', 'До 30 продукта или 3 локации', '40 обработени снимки', 'Професионален ретуш', 'Онлайн галерия'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Корпоративен', 'price' => 975.79, 'price_eur' => 499, 'description' => 'Целодневно, фото + видео', 'features' => ['Целодневно заснемане', 'Неограничен брой продукти/локации', '80+ обработени снимки', 'Кратко рекламно видео 30 сек.', 'Професионален ретуш', 'Експресно предаване'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\CommercialPackage::create($pkg);
            }
        }
    }

    private function seedFamilyPackages()
    {
        if (\App\Models\FamilyPackage::count() === 0) {
            foreach ([
                ['name' => 'Мини Сесия', 'price' => 154.51, 'price_eur' => 79, 'description' => '30 мин. фотосесия, 1 локация', 'features' => ['30 минути фотосесия', '1 локация', '15 обработени снимки', 'Онлайн галерия за сваляне'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Стандарт', 'price' => 291.32, 'price_eur' => 149, 'description' => '1 час фотосесия, до 2 локации', 'features' => ['1 час фотосесия', 'До 2 локации', '30 обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Премиум', 'price' => 447.82, 'price_eur' => 229, 'description' => '1.5 часа, до 3 локации + отпечатъци', 'features' => ['1.5 часа фотосесия', 'До 3 локации', '50 обработени снимки', 'Онлайн галерия за сваляне', 'Професионален ретуш', '5 отпечатъка 20x30 см'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\FamilyPackage::create($pkg);
            }
        }
    }

    private function seedPortraitPackages()
    {
        if (\App\Models\PortraitPackage::count() === 0) {
            foreach ([
                ['name' => 'Headshot', 'price' => 134.92, 'price_eur' => 69, 'description' => '20 мин. в студио, за LinkedIn/CV', 'features' => ['20 минути в студио', '1 фон', '5 обработени снимки', 'Кожен ретуш', 'Идеално за LinkedIn и CV'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Стандарт', 'price' => 252.11, 'price_eur' => 129, 'description' => '45 мин., 2 фона или локация', 'features' => ['45 минути фотосесия', '2 фона или 1 локация на открито', '15 обработени снимки', 'Пълен кожен ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Артистичен', 'price' => 427.93, 'price_eur' => 219, 'description' => '1.5 часа, студио + локация, креативен', 'features' => ['1.5 часа фотосесия', 'Студио + локация на открито', '25 обработени снимки', 'Креативно осветление и концепция', 'Пълен ретуш и цветова корекция', 'Онлайн галерия за сваляне'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\PortraitPackage::create($pkg);
            }
        }
    }

    private function seedAutomotivePackages()
    {
        if (\App\Models\AutomotivePackage::count() === 0) {
            foreach ([
                ['name' => 'Обява', 'price' => 173.96, 'price_eur' => 89, 'description' => '30 мин., за обяви за продажба', 'features' => ['30 минути заснемане', '1 локация', '10 обработени снимки', 'Екстериор + интериор', 'Цветова корекция'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Шоурум', 'price' => 349.89, 'price_eur' => 179, 'description' => '1 час, детайли и интериор', 'features' => ['1 час заснемане', 'До 2 локации', '20 обработени снимки', 'Екстериор, интериор и детайли', 'Професионален ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Кинематографичен', 'price' => 584.75, 'price_eur' => 299, 'description' => '2 часа, снимки + видео клип', 'features' => ['2 часа заснемане', 'Локация по избор', '30 обработени снимки', 'Видео клип 30 секунди', 'Динамични и статични кадри', 'Пълен ретуш и цветова корекция'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\AutomotivePackage::create($pkg);
            }
        }
    }

    private function seedArchitecturalPackages()
    {
        if (\App\Models\ArchitecturalPackage::count() === 0) {
            foreach ([
                ['name' => 'Имот', 'price' => 252.11, 'price_eur' => 129, 'description' => 'До 5 помещения, за обяви', 'features' => ['До 5 помещения', '15 HDR снимки', 'Широкоъгълна оптика', 'Цветова корекция', 'Идеално за обяви за продажба/наем'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Бизнес', 'price' => 486.70, 'price_eur' => 249, 'description' => 'До 10 помещения + дрон', 'features' => ['До 10 помещения + екстериор', '30 HDR снимки', 'Широкоъгълна оптика', 'Дрон кадри', 'Професионален ретуш', 'Онлайн галерия за сваляне'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Луксозен', 'price' => 780.27, 'price_eur' => 399, 'description' => 'Неограничени помещения + 360°', 'features' => ['Неограничен брой помещения', '50+ HDR снимки', 'Широкоъгълна оптика', 'Дрон кадри', 'Виртуална обиколка 360°', 'Пълен ретуш и цветова корекция'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\ArchitecturalPackage::create($pkg);
            }
        }
    }

    private function seedEventPackages()
    {
        if (\App\Models\EventPackage::count() === 0) {
            foreach ([
                ['name' => 'Бизнес (Фото + Видео)', 'price' => 584.75, 'price_eur' => 299, 'description' => 'До 3 часа заснемане на събитието', 'features' => ['3 часа фотозаснемане', '3 часа видеозаснемане', '70+ обработени снимки', 'Монтирано видео (1-2 мин.)', 'Онлайн галерия за сваляне', 'Предаване до 10 дни'], 'sort_order' => 1, 'is_featured' => false, 'is_visible' => true],
                ['name' => 'Корпоративен (Фото + Видео)', 'price' => 975.79, 'price_eur' => 499, 'description' => 'До 5 часа професионално заснемане', 'features' => ['5 часа фотозаснемане', '5 часа видеозаснемане', '120+ обработени снимки', 'Монтирано видео (2-3 мин.)', 'Social Media Reels клип', 'Онлайн галерия', 'Предаване до 7 дни'], 'sort_order' => 2, 'is_featured' => true, 'is_visible' => true],
                ['name' => 'Премиум (Фото + Видео)', 'price' => 1758.26, 'price_eur' => 899, 'description' => 'Цялостно заснемане до 10 часа + Дрон', 'features' => ['До 10 часа заснемане (Фото + Видео)', '250+ обработени снимки', 'Пълен филм от събитието (5+ мин.)', 'Дрон кадри (където е възможно)', 'Експресно предаване (48 часа)', 'Луксозна онлайн галерия'], 'sort_order' => 3, 'is_featured' => false, 'is_visible' => true],
            ] as $pkg) {
                \App\Models\EventPackage::create($pkg);
            }
        }
    }

    private function seedExtras()
    {
        $extrasMap = [
            'family' => [
                // Локация (radio group)
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'На открито', 'price_eur' => 0, 'icon_class' => 'fas fa-tree', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'В студио', 'price_eur' => 0, 'icon_class' => 'fas fa-home', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'На открито + Студио', 'price_eur' => 40, 'icon_class' => 'fas fa-map-marked-alt', 'description_bg' => null, 'display_order' => 3],
                // Extras (checkboxes)
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Допълнителни 10 снимки', 'price_eur' => 25, 'icon_class' => 'fas fa-images', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Експресна обработка (до 3 дни)', 'price_eur' => 35, 'icon_class' => 'fas fa-bolt', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => '5 отпечатъка 13x18', 'price_eur' => 20, 'icon_class' => 'fas fa-print', 'description_bg' => null, 'display_order' => 6],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Кратко видео 30 сек.', 'price_eur' => 50, 'icon_class' => 'fas fa-video', 'description_bg' => null, 'display_order' => 7],
            ],
            'portrait' => [
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'В студио', 'price_eur' => 0, 'icon_class' => 'fas fa-home', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'На открито', 'price_eur' => 0, 'icon_class' => 'fas fa-tree', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'Студио + Открито', 'price_eur' => 40, 'icon_class' => 'fas fa-map-marked-alt', 'description_bg' => null, 'display_order' => 3],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Грим и стилизация', 'price_eur' => 45, 'icon_class' => 'fas fa-paint-brush', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Допълнителни 5 снимки', 'price_eur' => 20, 'icon_class' => 'fas fa-images', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Експресна обработка (до 3 дни)', 'price_eur' => 30, 'icon_class' => 'fas fa-bolt', 'description_bg' => null, 'display_order' => 6],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Ретуш за соц. мрежи (кропнати)', 'price_eur' => 15, 'icon_class' => 'fas fa-mobile-alt', 'description_bg' => null, 'display_order' => 7],
            ],
            'automotive' => [
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'На място (вашата локация)', 'price_eur' => 0, 'icon_class' => 'fas fa-map-marker-alt', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Локация', 'label_bg' => 'Специална локация (по избор)', 'price_eur' => 30, 'icon_class' => 'fas fa-map-marked-alt', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Rolling shot (в движение)', 'price_eur' => 40, 'icon_class' => 'fas fa-tachometer-alt', 'description_bg' => null, 'display_order' => 3],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Допълнителни 10 снимки', 'price_eur' => 25, 'icon_class' => 'fas fa-images', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Дрон кадри', 'price_eur' => 50, 'icon_class' => 'fas fa-helicopter', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Видео клип 30 сек.', 'price_eur' => 60, 'icon_class' => 'fas fa-video', 'description_bg' => null, 'display_order' => 6],
            ],
            'architectural' => [
                ['input_type' => 'radio', 'group_name_bg' => 'Осветление', 'label_bg' => 'Само дневна светлина', 'price_eur' => 0, 'icon_class' => 'fas fa-sun', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Осветление', 'label_bg' => 'Дневна + нощна (twilight)', 'price_eur' => 60, 'icon_class' => 'fas fa-moon', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Дрон кадри', 'price_eur' => 50, 'icon_class' => 'fas fa-helicopter', 'description_bg' => null, 'display_order' => 3],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Виртуална обиколка 360°', 'price_eur' => 80, 'icon_class' => 'fas fa-vr-cardboard', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Планировка (floor plan)', 'price_eur' => 40, 'icon_class' => 'fas fa-drafting-compass', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Експресна обработка (до 3 дни)', 'price_eur' => 45, 'icon_class' => 'fas fa-bolt', 'description_bg' => null, 'display_order' => 6],
            ],
            'events' => [
                ['input_type' => 'radio', 'group_name_bg' => 'Формат', 'label_bg' => 'Само фотография', 'price_eur' => 0, 'icon_class' => 'fas fa-camera', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Формат', 'label_bg' => 'Фото + Видео', 'price_eur' => 120, 'icon_class' => 'fas fa-film', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Дрон кадри', 'price_eur' => 50, 'icon_class' => 'fas fa-helicopter', 'description_bg' => null, 'display_order' => 3],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Втори фотограф', 'price_eur' => 80, 'icon_class' => 'fas fa-user-plus', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Фотостена / Backdrop', 'price_eur' => 45, 'icon_class' => 'fas fa-th-large', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Експресно предаване (24ч)', 'price_eur' => 60, 'icon_class' => 'fas fa-shipping-fast', 'description_bg' => null, 'display_order' => 6],
            ],
            'commercial' => [
                ['input_type' => 'radio', 'group_name_bg' => 'Тип заснемане', 'label_bg' => 'Продуктова фотография', 'price_eur' => 0, 'icon_class' => 'fas fa-box-open', 'description_bg' => null, 'display_order' => 1],
                ['input_type' => 'radio', 'group_name_bg' => 'Тип заснемане', 'label_bg' => 'Бизнес портрети (екип)', 'price_eur' => 0, 'icon_class' => 'fas fa-users', 'description_bg' => null, 'display_order' => 2],
                ['input_type' => 'radio', 'group_name_bg' => 'Тип заснемане', 'label_bg' => 'Интериор / Екстериор', 'price_eur' => 0, 'icon_class' => 'fas fa-building', 'description_bg' => null, 'display_order' => 3],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Рекламно видео 30 сек.', 'price_eur' => 80, 'icon_class' => 'fas fa-video', 'description_bg' => null, 'display_order' => 4],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Дрон кадри', 'price_eur' => 50, 'icon_class' => 'fas fa-helicopter', 'description_bg' => null, 'display_order' => 5],
                ['input_type' => 'checkbox', 'group_name_bg' => 'Допълнителни услуги', 'label_bg' => 'Експресна обработка (до 3 дни)', 'price_eur' => 45, 'icon_class' => 'fas fa-bolt', 'description_bg' => null, 'display_order' => 6],
            ],
        ];

        foreach ($extrasMap as $slug => $extras) {
            $service = \App\Models\Service::where('slug', $slug)->first();
            if (!$service) continue;

            // Skip if already seeded
            if ($service->extras()->count() > 0) continue;

            foreach ($extras as $extra) {
                $service->extras()->create($extra);
            }
        }
    }

    private function seedPageContents()
    {
        $pages = [
            'family' => [
                ['section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Семейна Фотография'],
                ['section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Запечатайте най-ценните моменти със семейството си'],
                ['section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор Семейна Фотография'],
            ],
            'portrait' => [
                ['section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Портретна Фотография'],
                ['section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Артистични портрети, които разкриват вашата уникалност'],
                ['section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор Портретна Фотография'],
            ],
            'automotive' => [
                ['section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Автомобилна Фотография'],
                ['section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Заснемане на автомобили с внимание към всеки детайл'],
                ['section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор Автомобилна Фотография'],
            ],
            'architectural' => [
                ['section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Архитектурна Фотография'],
                ['section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Сгради, интериори и екстериори с перфектна композиция'],
                ['section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор Архитектурна Фотография'],
            ],
            'events' => [
                ['section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Събитийна Фотография'],
                ['section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Заснемане на фирмени, корпоративни и частни събития'],
                ['section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор Събитийна Фотография'],
            ],
        ];

        foreach ($pages as $pageSlug => $contents) {
            foreach ($contents as $content) {
                \App\Models\PageContent::firstOrCreate(
                    ['page_slug' => $pageSlug, 'section_slug' => $content['section_slug'], 'field_key' => $content['field_key']],
                    ['content_bg' => $content['content_bg']]
                );
            }
        }
    }
}
