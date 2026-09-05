<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * H1 = service + city. Only rows that still hold the original seeded slogan
 * are touched, so anything the owner already edited in Filament is preserved.
 */
return new class extends Migration
{
    private const TITLES = [
        'weddings' => [['Вашият Сватбен Ден', 'Сватбена фотография'], 'Сватбен фотограф и видеозаснемане във Варна'],
        'proms' => [['Абитуриентски Балове', 'Направи бала незабравим'], 'Фотограф и видео за абитуриентски бал във Варна'],
        'baptism' => [['Свето Кръщение'], 'Фотограф за кръщене във Варна'],
        'commercial' => [['Реклама и Бизнес'], 'Рекламна, продуктова и бизнес фотография във Варна'],
        'family' => [['Семейна Фотография'], 'Семеен фотограф във Варна'],
        'portrait' => [['Портретна Фотография'], 'Портретен фотограф във Варна'],
        'automotive' => [['Автомобилна Фотография'], 'Автомобилна фотография във Варна'],
        'architectural' => [['Архитектурна Фотография'], 'Архитектурна и интериорна фотография във Варна'],
        'events' => [['Събитийна Фотография'], 'Фото и видео за събития във Варна'],
    ];

    public function up(): void
    {
        foreach (self::TITLES as $slug => [$old, $new]) {
            DB::table('page_contents')
                ->where('page_slug', $slug)
                ->where('section_slug', 'hero')
                ->where('field_key', 'title')
                ->whereIn('content_bg', $old)
                ->update(['content_bg' => $new, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::TITLES as $slug => [$old, $new]) {
            DB::table('page_contents')
                ->where('page_slug', $slug)
                ->where('section_slug', 'hero')
                ->where('field_key', 'title')
                ->where('content_bg', $new)
                ->update(['content_bg' => $old[0], 'updated_at' => now()]);
        }
    }
};
