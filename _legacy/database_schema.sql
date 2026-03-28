-- TakeTwoStudio MySQL Database Schema
-- Version: 1.0
-- Description: Centralized database for managing website content, services, pricing, and customer inquiries.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. CONFIGURATION & CORE
-- --------------------------------------------------------

-- Table for global site settings (SEO, Contact, Social)
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for team members
CREATE TABLE IF NOT EXISTS `team_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `role_bg` VARCHAR(100) NOT NULL,
    `role_en` VARCHAR(100),
    `bio_bg` TEXT,
    `bio_en` TEXT,
    `image_path` VARCHAR(255),
    `display_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for partner/brand logos
CREATE TABLE IF NOT EXISTS `partners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `logo_path` VARCHAR(255) NOT NULL,
    `website_url` VARCHAR(255),
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 2. CONTENT & TESTIMONIALS
-- --------------------------------------------------------

-- Table for testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(100) NOT NULL,
    `content_bg` TEXT NOT NULL,
    `content_en` TEXT,
    `rating` INT DEFAULT 5,
    `event_date` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 3. PORTFOLIO
-- --------------------------------------------------------

-- Table for portfolio categories
CREATE TABLE IF NOT EXISTS `portfolio_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE, -- e.g., 'weddings', 'proms'
    `name_bg` VARCHAR(100) NOT NULL,
    `subtitle_bg` VARCHAR(100), -- e.g., 'Спомени за цял живот'
    `name_en` VARCHAR(100),
    `description_bg` TEXT,
    `cover_image` VARCHAR(255),
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for portfolio items (Images/Videos)
CREATE TABLE IF NOT EXISTS `portfolio_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT,
    `sub_category` VARCHAR(50), -- e.g., 'ads', 'product', 'imoti' (for commercial)
    `item_type` ENUM('image', 'video') DEFAULT 'image',
    `file_path` VARCHAR(255) NOT NULL,
    `thumbnail_path` VARCHAR(255),
    `alt_text_bg` VARCHAR(255),
    `is_featured` BOOLEAN DEFAULT FALSE,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `portfolio_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 4. DYNAMIC PAGE CONTENT
-- --------------------------------------------------------

-- Table for managing arbitrary text blocks on the site
CREATE TABLE IF NOT EXISTS `page_contents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(50) NOT NULL, -- index, weddings, etc.
    `section_slug` VARCHAR(50) NOT NULL, -- hero, about, footer_seo
    `field_key` VARCHAR(50) NOT NULL, -- title, subtitle, content
    `content_bg` TEXT NOT NULL,
    `content_en` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `page_section_field` (`page_slug`, `section_slug`, `field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 5. ADVANCED SITE MANAGEMENT
-- --------------------------------------------------------

-- Table for FAQs
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(50), -- e.g., 'baptism'
    `question_bg` TEXT NOT NULL,
    `answer_bg` TEXT NOT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Navigation Menu
CREATE TABLE IF NOT EXISTS `navigation_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `label_bg` VARCHAR(100) NOT NULL,
    `link_url` VARCHAR(255) NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    FOREIGN KEY (`parent_id`) REFERENCES `navigation_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for "Why Choose Us" / Reasons
CREATE TABLE IF NOT EXISTS `reasons_to_choose` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(50), -- e.g., 'weddings', 'proms'
    `icon_class` VARCHAR(50) NOT NULL,
    `title_bg` VARCHAR(100) NOT NULL,
    `content_bg` TEXT NOT NULL,
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Social Media Links (Detailed)
CREATE TABLE IF NOT EXISTS `social_media` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform_name` VARCHAR(50) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `icon_class` VARCHAR(50) NOT NULL,
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 6. SERVICES & CALCULATION LOGIC
-- --------------------------------------------------------

-- Table for main services
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `name_bg` VARCHAR(100) NOT NULL,
    `description_bg` TEXT,
    `icon_class` VARCHAR(50), -- e.g., 'fas fa-heart'
    `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for service packages (base prices)
CREATE TABLE IF NOT EXISTS `service_packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT NOT NULL,
    `name_bg` VARCHAR(100) NOT NULL,
    `price_eur` DECIMAL(10, 2) NOT NULL,
    `description_bg` TEXT,
    `is_default` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for service add-ons/extras
CREATE TABLE IF NOT EXISTS `service_extras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT NOT NULL,
    `input_type` ENUM('checkbox', 'radio', 'number') DEFAULT 'checkbox',
    `group_name_bg` VARCHAR(100), -- e.g., 'Video Resolution'
    `label_bg` VARCHAR(100) NOT NULL,
    `price_eur` DECIMAL(10, 2) NOT NULL,
    `description_bg` VARCHAR(255),
    `display_order` INT DEFAULT 0,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 5. INQUIRIES & TRANSACTIONS
-- --------------------------------------------------------

-- Table for customer inquiries (from calculators and contact forms)
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_type` VARCHAR(50), -- wedding, prom, baptism, commercial, general
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(100),
    `customer_phone` VARCHAR(30) NOT NULL,
    `event_date` DATE,
    `school_class` VARCHAR(100), -- specific to proms
    `selected_details` TEXT, -- JSON or stringified options chosen
    `final_price_eur` DECIMAL(10, 2),
    `message` TEXT,
    `status` ENUM('new', 'contacted', 'booked', 'completed', 'cancelled') DEFAULT 'new',
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- FULL DATA POPULATION (Real data from TakeTwoStudio)
-- --------------------------------------------------------

-- 1. SITE SETTINGS
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES 
('site_title', 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603', 'Primary SEO title'),
('site_description', 'Търсите сватбен фотограф във Варна? Take Two Studio 1603 предлагает професионално фото и видеозаснемане за сватби, абитуриентски балове и кръщенета в цялата страна. 4K качество и дрон услуги.', 'Primary SEO description'),
('site_keywords', 'сватбен фотограф варна, видеозаснемане сватба, фотограф за бал, заснемане на кръщене, професионална фотосесия варна, дрон услуги, Take Two Studio 1603', 'Primary SEO keywords'),
('contact_phone', '+359886190124', 'Primary contact phone'),
('contact_email', 'info@taketwostudio1603.com', 'Primary contact email'),
('contact_address', 'ж.к. Възраждане IV 1603, Варна, България', 'Primary contact address'),
('social_facebook', 'https://www.facebook.com/taketwostudio1603', 'Facebook page URL'),
('social_instagram', 'https://www.instagram.com/taketwostudio1603', 'Instagram profile URL'),
('logo_white', 'https://taketwostudio1603.com/css/img/logo-tts-white.webp', 'White Logo Path'),
('logo_dark', 'https://taketwostudio1603.com/css/img/logo-tts-dark.webp', 'Dark Logo Path');

-- 2. TEAM MEMBERS
INSERT INTO `team_members` (`name`, `role_bg`, `bio_bg`, `image_path`, `display_order`) VALUES 
('Симеон Тодоров', 'Фотограф / Бизнес Развитие', 'С над 10 години професионален опит във фотографията, Симеон е движещата сила зад Take Two Studio. Той съчетава организационния усет с артистичното виждане.', 'https://taketwostudio1603.com/css/img/ST.png', 1),
('Християна Гинчева', 'Фотограф / Графичен Дизайнер', 'Християна е визуалният магьосник на екипа. С професионалния си опит в графичния дизайн, тя вижда детайли, които другите пропускат.', 'https://taketwostudio1603.com/css/img/HG.jpg', 2),
('Пресиан Григоров', 'Видеограф / Дрон пилот', 'Пресиан има над 10 години опит в създаването на завладяващи видео разкази. Професионалният му път преминава през киното и телевизията.', 'https://taketwostudio1603.com/css/img/PG.png', 3);

-- 3. PARTNERS
INSERT INTO `partners` (`name`, `logo_path`, `display_order`) VALUES 
('Ardes', 'https://taketwostudio1603.com/css/img/Logos/Ardes%20logo%20without%20slogan%20expanded@4x.png', 1),
('Boxeroff', 'https://taketwostudio1603.com/css/img/Logos/boxeroff.png', 2),
('Hotel Continental', 'https://taketwostudio1603.com/css/img/Logos/hotel-continental.png', 3),
('Aspal', 'https://taketwostudio1603.com/css/img/Logos/aspal.png', 4),
('Cheers', 'https://taketwostudio1603.com/css/img/Logos/Cheers%20loog.jpg', 5),
('Drivably', 'https://taketwostudio1603.com/css/img/Logos/drivably%20logo.png', 6),
('Senshi', 'https://taketwostudio1603.com/css/img/Logos/senshi-logo-big.png', 7),
('Kamenitza', 'https://taketwostudio1603.com/css/img/Logos/Каменица.png', 8),
('Maxsuda', 'https://taketwostudio1603.com/css/img/Logos/maxsuda.jpeg', 9),
('Moonlight', 'https://taketwostudio1603.com/css/img/Logos/moonlight%20logo.jpg', 10);

-- 4. TESTIMONIALS
INSERT INTO `testimonials` (`client_name`, `content_bg`) VALUES 
('Неделина и Християн', 'Страхотни професионалисти! Направиха сватбения ни ден незабравим.'),
('Валентина', 'Изключително отношение и креативност.'),
('Japan Place Varna', 'Коректност, бързина и качество.');

-- 5. SERVICES
INSERT INTO `services` (`id`, `slug`, `name_bg`, `short_description_bg`, `icon_class`) VALUES 
(1, 'weddings', 'Сватбена Фотография', 'Цялостно заснемане.', 'fas fa-heart'),
(2, 'proms', 'Абитуриентски Балове', 'Индивидуални фотосесии.', 'fas fa-user-graduate'),
(3, 'baptism', 'Кръщенета', 'Спомени за цял живот', 'fas fa-star'),
(4, 'commercial', 'Реклама и Бизнес', 'Имиджови клипове.', 'fas fa-briefcase'),
(5, 'video', 'Видеозаснемане 4K', 'Кинематографични филми.', 'fas fa-video');

-- 6. SERVICE PACKAGES (Base options)
-- Weddings
INSERT INTO `service_packages` (`service_id`, `name_bg`, `price_eur`, `is_default`) VALUES 
(1, 'Видео заснемане: Един оператор', 890.00, 1),
(1, 'Видео заснемане: Двама оператори', 1145.00, 0),
(1, 'Фото заснемане: Един фотограф', 890.00, 1),
(1, 'Фото заснемане: Двама фотографи', 1145.00, 0);

-- Baptism
INSERT INTO `service_packages` (`service_id`, `name_bg`, `price_eur`, `is_default`) VALUES 
(3, 'Фотография', 120.00, 1),
(3, 'Видеозаснемане', 130.00, 0),
(3, 'Комбо (Фото + Видео)', 220.00, 0);

-- Proms
INSERT INTO `service_packages` (`service_id`, `name_bg`, `price_eur`, `is_default`) VALUES 
(2, 'ПАРТИ ПАКЕТ', 100.00, 1),
(2, 'ЛУКС ПАКЕТ', 110.00, 0);

-- 7. SERVICE EXTRAS
-- Weddings Photo Extras
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(1, 'checkbox', 'Фото Добавки', 'Следсватбена фотосесия', 110.00),
(1, 'checkbox', 'Фото Добавки', 'Допълнително осветление', 130.00);

-- Weddings Video Specials
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(1, 'radio', 'Дължина на филма', 'Филм до 60 мин', -25.00),
(1, 'radio', 'Дължина на филма', 'Филм до 90 мин (Стандарт)', 0.00),
(1, 'radio', 'Дължина на филма', 'Филм до 120 мин', 50.00),
(1, 'radio', 'Резолюция', '4K UHD (Стандарт)', 0.00),
(1, 'radio', 'Резолюция', '6K', 50.00);

-- Weddings Video Extras
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(1, 'checkbox', 'Видео Екстри', 'Аудио пакет', 50.00),
(1, 'checkbox', 'Видео Екстри', 'Дрон', 50.00),
(1, 'checkbox', 'Видео Екстри', 'Видео визитки (Shorts/Reels)', 100.00);

-- Weddings Delivery
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(1, 'radio', 'Получаване', 'Облак + Флашка в кутийка (Стандарт)', 0.00),
(1, 'radio', 'Получаване', 'Луксозна дървена кутия с флашка', 40.00),
(1, 'radio', 'Получаване', 'Пълен пакет: Кутия + Флашка + Албум', 120.00);

-- Baptism Scope & Extras
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(3, 'radio', 'Обхват', 'Само Църква/Ритуал', 0.00),
(3, 'radio', 'Обхват', 'Църква + Ресторант', 30.00),
(3, 'checkbox', 'Екстри', 'Фотокнига', 26.00),
(3, 'checkbox', 'Екстри', 'Флашка', 6.00),
(3, 'checkbox', 'Екстри', 'Експресна обработка (3 дни)', 50.00);

-- Proms Extras
INSERT INTO `service_extras` (`service_id`, `input_type`, `group_name_bg`, `label_bg`, `price_eur`) VALUES 
(2, 'checkbox', 'Екстри', 'Студийна фотосесия', 25.00),
(2, 'radio', 'Албум', 'Без албум', 0.00),
(2, 'radio', 'Албум', 'Фото книга (10x15)', 15.00),
(2, 'radio', 'Албум', 'Голяма фото книга', 30.00),
(2, 'checkbox', 'Екстри', 'Запис на флашка (USB)', 5.00);

-- 8. PORTFOLIO CATEGORIES
INSERT INTO `portfolio_categories` (`id`, `slug`, `name_bg`, `subtitle_bg`, `cover_image`) VALUES 
(1, 'weddings', 'Сватби', 'Запечатайте Любовта', 'https://images.pixieset.com/73162136/bd1e01a7c78224ead9d23b44867bc852-cover-large.jpg'),
(2, 'proms', 'Балове', 'Енергията на новия ден', 'https://taketwostudio1603.com/css/img/prom.jpg'),
(3, 'baptism', 'Кръщенета', 'Спомени за цял живот', 'https://taketwostudio1603.com/css/img/krustene.jpg'),
(4, 'commercial', 'Реклама', 'Продавай със стил', 'https://taketwostudio1603.com/css/img/реклама.jpg');

-- 9. PAGE CONTENTS (Dynamic SEO Texts)
-- Index Page
INSERT INTO `page_contents` (`page_slug`, `section_slug`, `field_key`, `content_bg`) VALUES 
('index', 'hero', 'title', 'Емоциите, които остават'),
('index', 'hero', 'subtitle', 'Професионална фотография и видеозаснемане'),
('index', 'about', 'title', 'Повече от обектив'),
('index', 'about', 'content', 'Take Two Studio 1603 не е просто екип от фотографи и видеографи. Ние сме разказвачи на истории, които вярват, че всеки важен момент заслужава да бъде превърнат в изкуство.'),
('index', 'footer_seo', 'content', 'Take Two Studio 1603 е вашето студио за професионални фото и видео услуги. Като водещи фотографи във Варна, ние предлагаме цялостно заснемане на сватби, кинематографични сватбени филми, заснемане на семейни празници и бизнес събития.');

-- Weddings Page
INSERT INTO `page_contents` (`page_slug`, `section_slug`, `field_key`, `content_bg`) VALUES 
('weddings', 'hero', 'title', 'Вашият Сватбен Ден'),
('weddings', 'hero', 'subtitle', 'Изкуството да улавяме любовта'),
('weddings', 'calculator', 'title', 'Сватбен Калкулатор');

-- Commercial Page
INSERT INTO `page_contents` (`page_slug`, `section_slug`, `field_key`, `content_bg`) VALUES 
('commercial', 'hero', 'title', 'Реклама и Бизнес'),
('commercial', 'hero', 'subtitle', 'Визуална идентичност, която продава'),
('commercial', 'intro', 'content', 'В дигиталната ера визуалното съдържание е лицето на вашия бизнес. В Take Two Studio 1603 създаваме висококачествено фото и видео съдържание за брандове, които искат да се отличат.'),
('commercial', 'services', 'title', 'Корпоративни Услуги');

-- Baptism Page
INSERT INTO `page_contents` (`page_slug`, `section_slug`, `field_key`, `content_bg`) VALUES 
('baptism', 'hero', 'title', 'Свето Кръщение'),
('baptism', 'hero', 'subtitle', 'Първият важен празник на вашето дете'),
('baptism', 'calculator', 'title', 'Калкулатор за Кръщене');

-- Proms Page
INSERT INTO `page_contents` (`page_slug`, `section_slug`, `field_key`, `content_bg`) VALUES 
('proms', 'hero', 'title', 'Абитуриентски Балове'),
('proms', 'hero', 'subtitle', 'Енергията на новото начало'),
('proms', 'calculator', 'title', 'Абитуриентски Калкулатор');

-- 10. PORTFOLIO ITEMS (FULL DATA)
-- Commercial Portfolio
INSERT INTO `portfolio_items` (`category_id`, `sub_category`, `file_path`, `alt_text_bg`) VALUES 
(4, 'ads', 'css/img/ads/topa-na-banketa-momcheto-fyre.jpg', 'TOPA NA BANKETA'),
(4, 'ads', 'css/img/ads/moonlight.jpg', 'Moonlight Event Center'),
(4, 'ads', 'css/img/ads/razors.jpg', 'Razors'),
(4, 'product', 'css/img/ads/product.png', 'Maksuda'),
(4, 'product', 'css/img/ads/dress.jpg', 'Tailor Boutique'),
(4, 'product', 'css/img/ads/clothes.jpg', 'Calieate'),
(4, 'ads', 'css/img/ads/fyre.jpg', 'Fyre'),
(4, 'product', 'css/img/ads/masterchef.jpg', 'Master chef'),
(4, 'imoti', 'css/img/ads/hotel.jpg', 'Hotel Continental'),
(4, 'events', 'css/img/ads/subitie.jpg', 'Каменица'),
(4, 'drone', 'css/img/ads/drone.jpg', 'Дрон');

-- Weddings Portfolio
INSERT INTO `portfolio_items` (`category_id`, `file_path`) VALUES 
(1, 'https://images.pixieset.com/73162136/e8a0109fb71cbeb2c86fa1d02c97690c-xxlarge.jpg'),
(1, 'https://images.pixieset.com/73162136/47f892089cb94fa67e447eb500638401-xxlarge.jpg'),
(1, 'https://images.pixieset.com/73162136/7b9de3bfe35d52f48da9f46a94107d6d-xxlarge.jpg'),
(1, 'https://images.pixieset.com/73162136/8fb075dc6414f7b8f2d9d7f09ea483ca-xxlarge.jpg'),
(1, 'css/img/Edited_IMG_9662-2.jpg'),
(1, 'https://images.pixieset.com/73162136/c7cc602dcb2ddc1969c299a4bb367102-xxlarge.JPG'),
(1, 'https://images.pixieset.com/73162136/e0463c3250cac08741fe107b210f267b-xxlarge.jpg'),
(1, 'css/img/Edited_IMG_9630.jpg'),
(1, 'https://images.pixieset.com/73162136/c1166a22ee5105bc15f5eff4404e5492-xxlarge.JPG');

-- Baptism Portfolio
INSERT INTO `portfolio_items` (`category_id`, `file_path`) VALUES 
(3, 'https://taketwostudio1603.com/css/img/krustene.jpg'),
(3, 'https://taketwostudio1603.com/css/img/krustene.jpg'),
(3, 'https://taketwostudio1603.com/css/img/krustene.jpg');

-- Proms Portfolio
INSERT INTO `portfolio_items` (`category_id`, `file_path`) VALUES 
(2, 'https://images.pixieset.com/68309375/ed17c1d3bd0de0d96671e76927b2f09f-xlarge.jpeg'),
(2, 'https://images.pixieset.com/68309375/9e1ebea2b515992b19c7be5d39003925-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/04be90ac436e1623b4dddce572f82621-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/706ee1d591f72b160152e6acab93c8c1-xlarge.jpeg'),
(2, 'https://images.pixieset.com/68309375/0d39645088ec6cb3a7e4e377b84c301a-xlarge.jpeg'),
(2, 'https://images.pixieset.com/68309375/0a5d71d8e8cfd2f719515af85c2eb2db-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/04e9671bb821a4c42d115b9ce46392c0-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/6601590a5f9e5914e5b0fd11f279ddc3-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/fe72df8ac54d1aa997e38eb4fdaabc9f-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/a6683c6780f913852a0dd1170ad0ee44-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/45bdbb033b1406dc310e61df7fe71ecd-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/ad12d3d7cdf4fceaca842c31dd1807c4-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/9649ec99b6bbf0b181ec90b9df6979a7-xlarge.jpg'),
(2, 'https://images.pixieset.com/68309375/cc17c9694f36567a56a13ffc2d2c9794-xlarge.jpeg');

-- 11. ADVANCED DATA POPULATION
-- FAQs (from baptism.html)
INSERT INTO `faqs` (`page_slug`, `question_bg`, `answer_bg`, `display_order`) VALUES 
('baptism', 'Колко време отнема заснемането на Свето Кръщение?', 'Самият църковен ритуал обикновено трае около 40-50 минути. Ние винаги сме там 15-20 минути по-рано, за да снимаме детайлите и гостите. След ритуала отделяме време за семейна фотосесия пред църквата. Общо ангажиментът е около 1.5 часа (за пакет "Само Църква").', 1),
('baptism', 'Кога получаваме снимките и видеото?', 'Стандартният срок за предаване на обработените кадри и видеото е до 30 работни дни. Ако имате нужда от материалите по-бързо, предлагаме услуга "Експресна обработка" (до 3 дни).', 2),
('baptism', 'Снимате ли в ресторанта?', 'Да, предлагаме разширен пакет, който включва и заснемане на тържеството в ресторанта (посрещане, разрязване на питата, торта и весели моменти с гостите).', 3);

-- Navigation Items
INSERT INTO `navigation_items` (`label_bg`, `link_url`, `display_order`) VALUES 
('За нас', '#about', 1),
('Портфолио', '#portfolio', 2),
('Услуги', '#services', 3),
('Контакти', '#contact', 4);

-- Reasons to Choose (from weddings.html)
INSERT INTO `reasons_to_choose` (`page_slug`, `icon_class`, `title_bg`, `content_bg`, `display_order`) VALUES 
('weddings', 'fas fa-user-tie', 'Професионализъм', 'Разбираме значението на този ден и се стремим да надхвърлим очакванията ви.', 1),
('weddings', 'fas fa-camera-retro', 'Модерна техника', 'Работим с най-добрите фото и видео технологии за перфектно качество.', 2),
('weddings', 'fas fa-lightbulb', 'Креативност', 'Всеки проект е уникален. Създаваме истории, които ще помните завинаги.', 3),
('weddings', 'fas fa-tags', 'Достъпни пакети', 'Предлагаме гъвкави ценови опции, без компромис с качеството.', 4);

-- Reasons to Choose (from proms.html)
INSERT INTO `reasons_to_choose` (`page_slug`, `icon_class`, `title_bg`, `content_bg`, `display_order`) VALUES 
('proms', 'fas fa-graduation-cap', 'Опит с абитуриенти', 'Всяка година работим с десетки випуски. Знаем точно кога и как да уловим най-доброто от този ден.', 1),
('proms', 'fas fa-film', 'Кино визия', 'Използваме професионални камери, дрон и модерна обработка, за да направим видео като от филм.', 2),
('proms', 'fas fa-user-friends', 'Персонален подход', 'Ние слушаме твоите идеи – от мястото за фотосесия до стила на снимките. Всичко е съобразено с теб.', 3),
('proms', 'fas fa-tag', 'Изгодни пакети', 'Имаме готови пакети с фиксирани цени, които покриват всичко – без скрити такси и изненади.', 4);

-- Social Media
INSERT INTO `social_media` (`platform_name`, `url`, `icon_class`, `display_order`) VALUES 
('Facebook', 'https://www.facebook.com/taketwostudio1603', 'fab fa-facebook-f', 1),
('Instagram', 'https://www.instagram.com/taketwostudio1603', 'fab fa-instagram', 2);
