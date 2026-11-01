<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every service page of the site needs a `services` row: the admin menu (Услуги → …)
 * opens it, and its extras / promotions / delivery days hang on services.id.
 * Some databases miss rows (e.g. family, automotive), so their extras could not be
 * saved. Creates only missing rows; existing ones are untouched.
 */
return new class extends Migration
{
    /** slug => name (same names as App\Support\Seo\ServiceCatalog). */
    private const SERVICES = [
        'proms' => 'Абитуриентски балове',
        'graduation' => 'Пред-бална фотосесия',
        'weddings' => 'Сватбена фотография и видеозаснемане',
        'baptism' => 'Кръщенета',
        'commercial' => 'Рекламна, продуктова и бизнес фотография',
        'family' => 'Семейна фотография',
        'portrait' => 'Портретна фотография',
        'automotive' => 'Автомобилна фотография',
        'architectural' => 'Архитектурна и интериорна фотография',
        'events' => 'Фото и видео за събития',
    ];

    public function up(): void
    {
        // A fresh install gets its services from the seeders (with fixed ids).
        if (! DB::table('services')->exists()) {
            return;
        }

        $order = (int) DB::table('services')->max('sort_order');

        foreach (self::SERVICES as $slug => $name) {
            if (DB::table('services')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('services')->insert([
                'slug' => $slug,
                'name_bg' => $name,
                'is_active' => true,
                'sort_order' => ++$order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Kept on purpose: the owner may already have filled these services in.
    }
};
