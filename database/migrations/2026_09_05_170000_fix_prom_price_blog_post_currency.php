<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The seeded post "Спестете бюджет: Защо 195 лв. на ученик..." contradicted the
 * EUR prices shown everywhere else (Bulgaria adopted the euro on 2026-01-01).
 * Rewrites only the BGN wording; anything else the owner edited stays.
 */
return new class extends Migration
{
    private const SLUG = 'speistete-budjet-balno-zasnemane-varna';

    public function up(): void
    {
        $post = DB::table('blog_posts')->where('slug', self::SLUG)->first();

        if (! $post) {
            return;
        }

        $fix = fn (?string $text) => $text === null ? null : str_replace(
            ['195,58 лв.', '195.58 лв.', '195 лв.'],
            ['100 €', '100 €', '100 €'],
            $text
        );

        DB::table('blog_posts')->where('id', $post->id)->update([
            'title' => str_contains($post->title, '195 лв.') ? 'Колко струва фото и видео за бала на ученик във Варна и защо пакетната цена спестява пари' : $post->title,
            'meta_title' => ($post->meta_title && str_contains($post->meta_title, '195 лв.')) ? 'Цена за бално заснемане на ученик във Варна (в евро)' : $post->meta_title,
            'excerpt' => $fix($post->excerpt),
            'meta_description' => $fix($post->meta_description),
            'body' => $fix($post->body),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Content edit; nothing to restore safely.
    }
};
