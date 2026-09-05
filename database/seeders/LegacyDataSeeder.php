<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LegacyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. SITE SETTINGS
        \App\Models\SiteSetting::create([
            'setting_key' => 'site_title',
            'setting_value' => 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603',
            'description' => 'Primary SEO title'
        ]);
        \App\Models\SiteSetting::create([
            'setting_key' => 'site_description',
            'setting_value' => 'Търсите сватбен фотограф във Варна? Take Two Studio 1603 предлагает професионално фото и видеозаснемане за сватби, абитуриентски балове и кръщенета в цялата страна. 4K качество и дрон услуги.',
            'description' => 'Primary SEO description'
        ]);
        \App\Models\SiteSetting::create([
            'setting_key' => 'site_keywords',
            'setting_value' => 'сватбен фотограф варна, видеозаснемане сватба, фотограф за бал, заснемане на кръщене, професионална фотосесия варна, дрон услуги, Take Two Studio 1603',
            'description' => 'Primary SEO keywords'
        ]);
        $this->call(SiteSettingsSeeder::class);

        \App\Models\SiteSetting::create([
            'setting_key' => 'logo_white',
            'setting_value' => 'https://taketwostudio1603.com/css/img/logo-tts-white.webp',
            'description' => 'White Logo Path'
        ]);
        \App\Models\SiteSetting::create([
            'setting_key' => 'logo_dark',
            'setting_value' => 'https://taketwostudio1603.com/css/img/logo-tts-dark.webp',
            'description' => 'Dark Logo Path'
        ]);

        // 2. TEAM MEMBERS
        \App\Models\TeamMember::create([
            'name' => 'Симеон Тодоров',
            'role_bg' => 'Фотограф / Бизнес Развитие',
            'bio_bg' => 'С над 10 години професионален опит във фотографията, Симеон е движещата сила зад Take Two Studio. Той съчетава организационния усет с артистичното виждане.',
            'image_path' => 'https://taketwostudio1603.com/css/img/ST.png',
            'display_order' => 1
        ]);
        \App\Models\TeamMember::create([
            'name' => 'Християна Гинчева',
            'role_bg' => 'Фотограф / Графичен Дизайнер',
            'bio_bg' => 'Християна е визуалният магьосник на екипа. С професионалния си опит в графичния дизайн, тя вижда детайли, които другите пропускат.',
            'image_path' => 'https://taketwostudio1603.com/css/img/HG.jpg',
            'display_order' => 2
        ]);
        \App\Models\TeamMember::create([
            'name' => 'Пресиан Григоров',
            'role_bg' => 'Видеограф / Дрон пилот',
            'bio_bg' => 'Пресиан има над 10 години опит в създаването на завладяващи видео разкази. Професионалният му път преминава през киното и телевизията.',
            'image_path' => 'https://taketwostudio1603.com/css/img/PG.png',
            'display_order' => 3
        ]);

        // 3. SERVICES
        $wedding = \App\Models\Service::create([
            'id' => 1,
            'slug' => 'weddings',
            'name_bg' => 'Сватбена Фотография',
            'description_bg' => 'Цялостно заснемане.',
            'icon_class' => 'fas fa-heart'
        ]);
        $prom = \App\Models\Service::create([
            'id' => 2,
            'slug' => 'proms',
            'name_bg' => 'Абитуриентски Балове',
            'description_bg' => 'Индивидуални фотосесии.',
            'icon_class' => 'fas fa-user-graduate'
        ]);
        $baptism = \App\Models\Service::create([
            'id' => 3,
            'slug' => 'baptism',
            'name_bg' => 'Кръщенета',
            'description_bg' => 'Спомени за цял живот',
            'icon_class' => 'fas fa-star'
        ]);
        $commercial = \App\Models\Service::create([
            'id' => 4,
            'slug' => 'commercial',
            'name_bg' => 'Реклама и Бизнес',
            'description_bg' => 'Имиджови клипове.',
            'icon_class' => 'fas fa-briefcase'
        ]);
        $video = \App\Models\Service::create([
            'id' => 5,
            'slug' => 'video',
            'name_bg' => 'Видеозаснемане 4K',
            'description_bg' => 'Кинематографични филми.',
            'icon_class' => 'fas fa-video'
        ]);

        // 4. SERVICE PACKAGES
        // Weddings
        \App\Models\ServicePackage::create([
            'service_id' => 1,
            'name_bg' => 'Видео заснемане: Един оператор',
            'price_eur' => 890.00,
            'is_default' => true
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 1,
            'name_bg' => 'Видео заснемане: Двама оператори',
            'price_eur' => 1145.00,
            'is_default' => false
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 1,
            'name_bg' => 'Фото заснемане: Един фотограф',
            'price_eur' => 890.00,
            'is_default' => true
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 1,
            'name_bg' => 'Фото заснемане: Двама фотографи',
            'price_eur' => 1145.00,
            'is_default' => false
        ]);
        
        // Baptism
        \App\Models\ServicePackage::create([
            'service_id' => 3,
            'name_bg' => 'Фотография',
            'price_eur' => 120.00,
            'is_default' => true
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 3,
            'name_bg' => 'Видеозаснемане',
            'price_eur' => 130.00,
            'is_default' => false
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 3,
            'name_bg' => 'Комбо (Фото + Видео)',
            'price_eur' => 220.00,
            'is_default' => false
        ]);

        // Proms
        \App\Models\ServicePackage::create([
            'service_id' => 2,
            'name_bg' => 'ПАРТИ ПАКЕТ',
            'price_eur' => 100.00,
            'is_default' => true
        ]);
        \App\Models\ServicePackage::create([
            'service_id' => 2,
            'name_bg' => 'ЛУКС ПАКЕТ',
            'price_eur' => 110.00,
            'is_default' => false
        ]);

        // 5. SERVICE EXTRAS
        // Weddings Photo Extras
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Фото Добавки',
            'label_bg' => 'Следсватбена фотосесия',
            'price_eur' => 110.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Фото Добавки',
            'label_bg' => 'Допълнително осветление',
            'price_eur' => 130.00
        ]);

        // Weddings Video Specials
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Дължина на филма',
            'label_bg' => 'Филм до 60 мин',
            'price_eur' => -25.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Дължина на филма',
            'label_bg' => 'Филм до 90 мин (Стандарт)',
            'price_eur' => 0.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Дължина на филма',
            'label_bg' => 'Филм до 120 мин',
            'price_eur' => 50.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Резолюция',
            'label_bg' => '4K UHD (Стандарт)',
            'price_eur' => 0.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Резолюция',
            'label_bg' => '6K',
            'price_eur' => 50.00
        ]);

        // Weddings Video Extras
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Видео Екстри',
            'label_bg' => 'Аудио пакет',
            'price_eur' => 50.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Видео Екстри',
            'label_bg' => 'Дрон',
            'price_eur' => 50.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Видео Екстри',
            'label_bg' => 'Видео визитки (Shorts/Reels)',
            'price_eur' => 100.00
        ]);

        // Weddings Delivery
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Получаване',
            'label_bg' => 'Облак + Флашка в кутийка (Стандарт)',
            'price_eur' => 0.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Получаване',
            'label_bg' => 'Луксозна дървена кутия с флашка',
            'price_eur' => 40.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 1,
            'input_type' => 'radio',
            'group_name_bg' => 'Получаване',
            'label_bg' => 'Пълен пакет: Кутия + Флашка + Албум',
            'price_eur' => 120.00
        ]);

        // Baptism Scope & Extras
        \App\Models\ServiceExtra::create([
            'service_id' => 3,
            'input_type' => 'radio',
            'group_name_bg' => 'Обхват',
            'label_bg' => 'Само Църква/Ритуал',
            'price_eur' => 0.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 3,
            'input_type' => 'radio',
            'group_name_bg' => 'Обхват',
            'label_bg' => 'Църква + Ресторант',
            'price_eur' => 30.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 3,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Екстри',
            'label_bg' => 'Фотокнига',
            'price_eur' => 26.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 3,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Екстри',
            'label_bg' => 'Флашка',
            'price_eur' => 6.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 3,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Екстри',
            'label_bg' => 'Експресна обработка (3 дни)',
            'price_eur' => 50.00
        ]);

        // Proms Extras
        \App\Models\ServiceExtra::create([
            'service_id' => 2,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Екстри',
            'label_bg' => 'Студийна фотосесия',
            'price_eur' => 25.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 2,
            'input_type' => 'radio',
            'group_name_bg' => 'Албум',
            'label_bg' => 'Без албум',
            'price_eur' => 0.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 2,
            'input_type' => 'radio',
            'group_name_bg' => 'Албум',
            'label_bg' => 'Фото книга (10x15)',
            'price_eur' => 15.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 2,
            'input_type' => 'radio',
            'group_name_bg' => 'Албум',
            'label_bg' => 'Голяма фото книга',
            'price_eur' => 30.00
        ]);
        \App\Models\ServiceExtra::create([
            'service_id' => 2,
            'input_type' => 'checkbox',
            'group_name_bg' => 'Екстри',
            'label_bg' => 'Запис на флашка (USB)',
            'price_eur' => 5.00
        ]);

        // 6. PORTFOLIO CATEGORIES
        \App\Models\PortfolioCategory::create([
            'id' => 1,
            'slug' => 'weddings',
            'name_bg' => 'Сватби',
            'subtitle_bg' => 'Запечатайте Любовта',
            'cover_image' => 'https://images.pixieset.com/73162136/bd1e01a7c78224ead9d23b44867bc852-cover-large.jpg'
        ]);
        \App\Models\PortfolioCategory::create([
            'id' => 2,
            'slug' => 'proms',
            'name_bg' => 'Балове',
            'subtitle_bg' => 'Енергията на новия ден',
            'cover_image' => 'https://taketwostudio1603.com/css/img/prom.jpg'
        ]);
        \App\Models\PortfolioCategory::create([
            'id' => 3,
            'slug' => 'baptism',
            'name_bg' => 'Кръщенета',
            'subtitle_bg' => 'Спомени за цял живот',
            'cover_image' => 'https://taketwostudio1603.com/css/img/krustene.jpg'
        ]);
        \App\Models\PortfolioCategory::create([
            'id' => 4,
            'slug' => 'commercial',
            'name_bg' => 'Реклама',
            'subtitle_bg' => 'Продавай със стил',
            'cover_image' => 'https://taketwostudio1603.com/css/img/реклама.jpg'
        ]);

        // 7. PORTFOLIO ITEMS
        // Commercial Portfolio
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'ads',
            'file_path' => 'css/img/ads/topa-na-banketa-momcheto-fyre.jpg',
            'alt_text_bg' => 'TOPA NA BANKETA'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'ads',
            'file_path' => 'css/img/ads/moonlight.jpg',
            'alt_text_bg' => 'Moonlight Event Center'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'ads',
            'file_path' => 'css/img/ads/razors.jpg',
            'alt_text_bg' => 'Razors'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'product',
            'file_path' => 'css/img/ads/product.png',
            'alt_text_bg' => 'Maksuda'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'product',
            'file_path' => 'css/img/ads/dress.jpg',
            'alt_text_bg' => 'Tailor Boutique'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'product',
            'file_path' => 'css/img/ads/clothes.jpg',
            'alt_text_bg' => 'Calieate'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'ads',
            'file_path' => 'css/img/ads/fyre.jpg',
            'alt_text_bg' => 'Fyre'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'product',
            'file_path' => 'css/img/ads/masterchef.jpg',
            'alt_text_bg' => 'Master chef'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'imoti',
            'file_path' => 'css/img/ads/hotel.jpg',
            'alt_text_bg' => 'Hotel Continental'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'events',
            'file_path' => 'css/img/ads/subitie.jpg',
            'alt_text_bg' => 'Каменица'
        ]);
        \App\Models\PortfolioItem::create([
            'category_id' => 4,
            'sub_category' => 'drone',
            'file_path' => 'css/img/ads/drone.jpg',
            'alt_text_bg' => 'Дрон'
        ]);

        // Weddings Portfolio
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/e8a0109fb71cbeb2c86fa1d02c97690c-xxlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/47f892089cb94fa67e447eb500638401-xxlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/7b9de3bfe35d52f48da9f46a94107d6d-xxlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/8fb075dc6414f7b8f2d9d7f09ea483ca-xxlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'css/img/Edited_IMG_9662-2.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/c7cc602dcb2ddc1969c299a4bb367102-xxlarge.JPG']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/e0463c3250cac08741fe107b210f267b-xxlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'css/img/Edited_IMG_9630.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 1, 'file_path' => 'https://images.pixieset.com/73162136/c1166a22ee5105bc15f5eff4404e5492-xxlarge.JPG']);

        // Baptism Portfolio
        \App\Models\PortfolioItem::create(['category_id' => 3, 'file_path' => 'https://taketwostudio1603.com/css/img/krustene.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 3, 'file_path' => 'https://taketwostudio1603.com/css/img/krustene.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 3, 'file_path' => 'https://taketwostudio1603.com/css/img/krustene.jpg']);

        // Proms Portfolio
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/ed17c1d3bd0de0d96671e76927b2f09f-xlarge.jpeg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/9e1ebea2b515992b19c7be5d39003925-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/04be90ac436e1623b4dddce572f82621-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/706ee1d591f72b160152e6acab93c8c1-xlarge.jpeg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/0d39645088ec6cb3a7e4e377b84c301a-xlarge.jpeg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/0a5d71d8e8cfd2f719515af85c2eb2db-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/04e9671bb821a4c42d115b9ce46392c0-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/6601590a5f9e5914e5b0fd11f279ddc3-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/fe72df8ac54d1aa997e38eb4fdaabc9f-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/a6683c6780f913852a0dd1170ad0ee44-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/45bdbb033b1406dc310e61df7fe71ecd-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/ad12d3d7cdf4fceaca842c31dd1807c4-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/9649ec99b6bbf0b181ec90b9df6979a7-xlarge.jpg']);
        \App\Models\PortfolioItem::create(['category_id' => 2, 'file_path' => 'https://images.pixieset.com/68309375/cc17c9694f36567a56a13ffc2d2c9794-xlarge.jpeg']);

        // 8. PAGE CONTENTS
        // Index Page
        \App\Models\PageContent::create(['page_slug' => 'index', 'section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Емоциите, които остават']);
        \App\Models\PageContent::create(['page_slug' => 'index', 'section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Професионална фотография и видеозаснемане']);
        \App\Models\PageContent::create(['page_slug' => 'index', 'section_slug' => 'about', 'field_key' => 'title', 'content_bg' => 'Повече от обектив']);
        \App\Models\PageContent::create(['page_slug' => 'index', 'section_slug' => 'about', 'field_key' => 'content', 'content_bg' => 'Take Two Studio 1603 не е просто екип от фотографи и видеографи. Ние сме разказвачи на истории, които вярват, че всеки важен момент заслужава да бъде превърнат в изкуство.']);
        \App\Models\PageContent::create(['page_slug' => 'index', 'section_slug' => 'footer_seo', 'field_key' => 'content', 'content_bg' => 'Take Two Studio 1603 е вашето студио за професионални фото и видео услуги. Като водещи фотографи във Варна, ние предлагаме цялостно заснемане на сватби, кинематографични сватбени филми, заснемане на семейни празници и бизнес събития.']);

        // Weddings Page
        \App\Models\PageContent::create(['page_slug' => 'weddings', 'section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Вашият Сватбен Ден']);
        \App\Models\PageContent::create(['page_slug' => 'weddings', 'section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Изкуството да улавяме любовта']);
        \App\Models\PageContent::create(['page_slug' => 'weddings', 'section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Сватбен Калкулатор']);

        // Commercial Page
        \App\Models\PageContent::create(['page_slug' => 'commercial', 'section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Реклама и Бизнес']);
        \App\Models\PageContent::create(['page_slug' => 'commercial', 'section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Визуална идентичност, която продава']);
        \App\Models\PageContent::create(['page_slug' => 'commercial', 'section_slug' => 'intro', 'field_key' => 'content', 'content_bg' => 'В дигиталната ера визуалното съдържание е лицето на вашия бизнес. В Take Two Studio 1603 създаваме висококачествено фото и видео съдържание за брандове, които искат да се отличат.']);
        \App\Models\PageContent::create(['page_slug' => 'commercial', 'section_slug' => 'services', 'field_key' => 'title', 'content_bg' => 'Корпоративни Услуги']);

        // Baptism Page
        \App\Models\PageContent::create(['page_slug' => 'baptism', 'section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Свето Кръщение']);
        \App\Models\PageContent::create(['page_slug' => 'baptism', 'section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Първият важен празник на вашето дете']);
        \App\Models\PageContent::create(['page_slug' => 'baptism', 'section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Калкулатор за Кръщене']);

        // Proms Page
        \App\Models\PageContent::create(['page_slug' => 'proms', 'section_slug' => 'hero', 'field_key' => 'title', 'content_bg' => 'Абитуриентски Балове']);
        \App\Models\PageContent::create(['page_slug' => 'proms', 'section_slug' => 'hero', 'field_key' => 'subtitle', 'content_bg' => 'Енергията на новото начало']);
        \App\Models\PageContent::create(['page_slug' => 'proms', 'section_slug' => 'calculator', 'field_key' => 'title', 'content_bg' => 'Абитуриентски Калкулатор']);

        // 9. FAQS
        \App\Models\Faq::create([
            'page_slug' => 'baptism',
            'question' => 'Колко време отнема заснемането на Свето Кръщение?',
            'answer' => 'Самият църковен ритуал обикновено трае около 40-50 минути. Ние винаги сме там 15-20 минути по-рано, за да снимаме детайлите и гостите. След ритуала отделяме време за семейна фотосесия пред църквата. Общо ангажиментът е около 1.5 часа (за пакет "Само Църква").',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'baptism',
            'question' => 'Кога получаваме снимките и видеото?',
            'answer' => 'Стандартният срок за предаване на обработените кадри и видеото е до 30 работни дни. Ако имате нужда от материалите по-бързо, предлагаме услуга "Експресна обработка" (до 3 дни).',
            'sort_order' => 2,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'baptism',
            'question' => 'Снимате ли в ресторанта?',
            'answer' => 'Да, предлагаме разширен пакет, който включва и заснемане на тържеството в ресторанта (посрещане, разрязване на питата, торта и весели моменти с гостите).',
            'sort_order' => 3,
            'is_visible' => true,
        ]);

        // Prom FAQs
        \App\Models\Faq::create([
            'page_slug' => 'proms',
            'question' => 'Колко струва фотограф за абитуриентски бал във Варна?',
            'answer' => 'Цените за фотозаснемане на абитуриентски бал зависят от избрания пакет – от индивидуална фотосесия до пълно покритие на изпращането, каненето и бала с видео и дрон. Можете да използвате нашия онлайн калкулатор на страницата за точна цена според вашите желания.',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'proms',
            'question' => 'Какво включва абитуриентската фотосесия и изпращане?',
            'answer' => 'Включва индивидуални художествени кадри на абитуриента, снимки с приятели и семейството по време на изпращането, пълна професионална обработка и онлайн галерия с висока резолюция.',
            'sort_order' => 2,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'proms',
            'question' => 'Предлагате ли и видеозаснемане с дрон за бала?',
            'answer' => 'Да, заснемаме кинематографични 4K видеоклипове и предлагаме въздушни кадри с дрон за максимално ефектно отразяване на събитието.',
            'sort_order' => 3,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'proms',
            'question' => 'Кога е най-подходящото време за резервация за бала?',
            'answer' => 'Препоръчваме да запазите дата още през есента или ранната пролет, тъй като най-желаните дати около баловете се запълват бързо.',
            'sort_order' => 4,
            'is_visible' => true,
        ]);

        // Wedding FAQs
        \App\Models\Faq::create([
            'page_slug' => 'weddings',
            'question' => 'Колко предварително трябва да резервираме за сватбено заснемане?',
            'answer' => 'Препоръчваме резервация поне 6–12 месеца предварително, тъй като сезонът е много натоварен. Колкото по-рано запазите датата, толкова по-сигурно ще имате нашия екип на своята сватба.',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'weddings',
            'question' => 'Работите ли извън Варна?',
            'answer' => 'Да, пътуваме из цяла България. За локации извън Варна може да се приложи такса за транспорт в зависимост от разстоянието. Свържете се с нас за конкретна оферта.',
            'sort_order' => 2,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'weddings',
            'question' => 'Кога получаваме снимките и видеото след сватбата?',
            'answer' => 'Стандартният срок за предаване на обработените кадри е до 60 работни дни. При нужда от по-бърза доставка предлагаме услуга за експресна обработка.',
            'sort_order' => 3,
            'is_visible' => true,
        ]);
        \App\Models\Faq::create([
            'page_slug' => 'weddings',
            'question' => 'Предлагате ли дрон заснемане на сватби?',
            'answer' => 'Да, предлагаме дрон кадри като допълнение към всеки пакет за сватбено заснемане. Дронът добавя кинематографична въздушна перспектива към вашия сватбен филм.',
            'sort_order' => 4,
            'is_visible' => true,
        ]);

        // 10. NAVIGATION ITEMS
        \App\Models\NavigationItem::create(['label_bg' => 'За нас', 'link_url' => '#about', 'display_order' => 1]);
        \App\Models\NavigationItem::create(['label_bg' => 'Портфолио', 'link_url' => '#portfolio', 'display_order' => 2]);
        \App\Models\NavigationItem::create(['label_bg' => 'Услуги', 'link_url' => '#services', 'display_order' => 3]);
        \App\Models\NavigationItem::create(['label_bg' => 'Контакти', 'link_url' => '#contact', 'display_order' => 4]);

        // 11. REASONS TO CHOOSE
        // Weddings
        \App\Models\ReasonToChoose::create(['page_slug' => 'weddings', 'icon_class' => 'fas fa-user-tie', 'title_bg' => 'Професионализъм', 'content_bg' => 'Разбираме значението на този ден и се стремим да надхвърлим очакванията ви.', 'display_order' => 1]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'weddings', 'icon_class' => 'fas fa-camera-retro', 'title_bg' => 'Модерна техника', 'content_bg' => 'Работим с най-добрите фото и видео технологии за перфектно качество.', 'display_order' => 2]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'weddings', 'icon_class' => 'fas fa-lightbulb', 'title_bg' => 'Креативност', 'content_bg' => 'Всеки проект е уникален. Създаваме истории, които ще помните завинаги.', 'display_order' => 3]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'weddings', 'icon_class' => 'fas fa-tags', 'title_bg' => 'Достъпни пакети', 'content_bg' => 'Предлагаме гъвкави ценови опции, без компромис с качеството.', 'display_order' => 4]);

        // Proms
        \App\Models\ReasonToChoose::create(['page_slug' => 'proms', 'icon_class' => 'fas fa-graduation-cap', 'title_bg' => 'Опит с абитуриенти', 'content_bg' => 'Всяка година работим с десетки випуски. Знаем точно кога и как да уловим най-доброто от този ден.', 'display_order' => 1]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'proms', 'icon_class' => 'fas fa-film', 'title_bg' => 'Кино визия', 'content_bg' => 'Използваме професионални камери, дрон и модерна обработка, за да направим видео като от филм.', 'display_order' => 2]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'proms', 'icon_class' => 'fas fa-user-friends', 'title_bg' => 'Персонален подход', 'content_bg' => 'Ние слушаме твоите идеи – от мястото за фотосесия до стила на снимките. Всичко е съобразено с теб.', 'display_order' => 3]);
        \App\Models\ReasonToChoose::create(['page_slug' => 'proms', 'icon_class' => 'fas fa-tag', 'title_bg' => 'Изгодни пакети', 'content_bg' => 'Имаме готови пакети с фиксирани цени, които покриват всичко – без скрити такси и изненади.', 'display_order' => 4]);

        // 12. SOCIAL MEDIA
        \App\Models\SocialMedia::create(['platform_name' => 'Facebook', 'url' => 'https://www.facebook.com/taketwostudio1603', 'icon_class' => 'fab fa-facebook-f', 'display_order' => 1]);
        \App\Models\SocialMedia::create(['platform_name' => 'Instagram', 'url' => 'https://www.instagram.com/taketwostudio1603', 'icon_class' => 'fab fa-instagram', 'display_order' => 2]);

        // 13. PARTNERS
        \App\Models\Partner::create(['name' => 'Ardes', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/Ardes%20logo%20without%20slogan%20expanded@4x.png', 'display_order' => 1]);
        \App\Models\Partner::create(['name' => 'Boxeroff', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/boxeroff.png', 'display_order' => 2]);
        \App\Models\Partner::create(['name' => 'Hotel Continental', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/hotel-continental.png', 'display_order' => 3]);
        \App\Models\Partner::create(['name' => 'Aspal', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/aspal.png', 'display_order' => 4]);
        \App\Models\Partner::create(['name' => 'Cheers', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/Cheers%20loog.jpg', 'display_order' => 5]);
        \App\Models\Partner::create(['name' => 'Drivably', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/drivably%20logo.png', 'display_order' => 6]);
        \App\Models\Partner::create(['name' => 'Senshi', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/senshi-logo-big.png', 'display_order' => 7]);
        \App\Models\Partner::create(['name' => 'Kamenitza', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/Каменица.png', 'display_order' => 8]);
        \App\Models\Partner::create(['name' => 'Maxsuda', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/maxsuda.jpeg', 'display_order' => 9]);
        \App\Models\Partner::create(['name' => 'Moonlight', 'logo_path' => 'https://taketwostudio1603.com/css/img/Logos/moonlight%20logo.jpg', 'display_order' => 10]);

        // 14. TESTIMONIALS
        \App\Models\Testimonial::create(['client_name' => 'Неделина и Християн', 'content_bg' => 'Страхотни професионалисти! Направиха сватбения ни ден незабравим.']);
        \App\Models\Testimonial::create(['client_name' => 'Валентина', 'content_bg' => 'Изключително отношение и креативност.']);
        \App\Models\Testimonial::create(['client_name' => 'Japan Place Varna', 'content_bg' => 'Коректност, бързина и качество.']);

    }
}
