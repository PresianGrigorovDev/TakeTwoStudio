<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One faqs table (page_slug, question, answer, sort_order, is_visible) instead of
 * five per-service tables + a hard-coded array in commercial.blade.php.
 * Idempotent; rows are matched on (page_slug, question) so re-running never duplicates.
 */
return new class extends Migration
{
    private const LEGACY = [
        'wedding_faqs' => 'weddings',
        'prom_faqs' => 'proms',
        'baptism_faqs' => 'baptism',
        'commercial_faqs' => 'commercial',
        'graduation_faqs' => 'proms', // /graduation is a 301 to /proms
    ];

    /** Previously hard-coded in resources/views/commercial.blade.php */
    private const COMMERCIAL_DEFAULTS = [
        ['Каква е цената за продуктова фотография?', 'Цената зависи от броя продукти, вида обработка и специфичните изисквания на проекта. Изпратете ни запитване с детайли и ще изготвим индивидуална оферта за вас.'],
        ['Правите ли рекламни видеа за социалните мрежи?', 'Да, създаваме кратко и динамично видео съдържание, оптимизирано за Instagram, Facebook, TikTok и YouTube. Работим с дрон, студийно осветление и модерна постпродукция.'],
        ['Снимате ли хотели, ресторанти и имоти?', 'Да, предлагаме интериорна и екстериорна фотография за хотели, ресторанти, фитнес зали и имоти. Дроните ни добавят въздушна перспектива, която се откроява.'],
        ['Работите ли с малък и среден бизнес?', 'Разбира се! Работим с бизнеси от всякакъв мащаб — от малки онлайн магазини до корпоративни компании. Всеки проект получава индивидуално внимание и подход.'],
    ];

    public function up(): void
    {
        // 1) Bring the generic table to the shared column set.
        Schema::table('faqs', function (Blueprint $table) {
            if (! Schema::hasColumn('faqs', 'question')) {
                $table->text('question')->nullable();
            }
            if (! Schema::hasColumn('faqs', 'answer')) {
                $table->text('answer')->nullable();
            }
            if (! Schema::hasColumn('faqs', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
            if (! Schema::hasColumn('faqs', 'is_visible')) {
                $table->boolean('is_visible')->default(true);
            }
        });

        if (Schema::hasColumn('faqs', 'question_bg')) {
            DB::table('faqs')->whereNull('question')->update([
                'question' => DB::raw('question_bg'),
                'answer' => DB::raw('answer_bg'),
            ]);
        }
        if (Schema::hasColumn('faqs', 'display_order')) {
            DB::table('faqs')->update(['sort_order' => DB::raw('display_order')]);
        }
        if (Schema::hasColumn('faqs', 'is_active')) {
            DB::table('faqs')->update(['is_visible' => DB::raw('is_active')]);
        }

        DB::table('faqs')->where('page_slug', 'baptisms')->update(['page_slug' => 'baptism']);

        // Old NOT NULL columns must go before new rows are inserted.
        Schema::table('faqs', function (Blueprint $table) {
            $drop = array_values(array_filter(['question_bg', 'answer_bg', 'display_order', 'is_active'], fn ($c) => Schema::hasColumn('faqs', $c)));
            if ($drop) {
                $table->dropColumn($drop);
            }
        });

        // 2) Move rows from the legacy tables.
        foreach (self::LEGACY as $table => $slug) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $offset = (int) DB::table('faqs')->where('page_slug', $slug)->max('sort_order');

            foreach (DB::table($table)->orderBy('sort_order')->orderBy('id')->get() as $row) {
                $this->insertIfMissing($slug, $row->question, $row->answer, $offset + (int) $row->sort_order + 1, (bool) ($row->is_visible ?? true));
            }
        }

        // 3) Commercial FAQs lived only in the Blade template until now.
        if (! DB::table('faqs')->where('page_slug', 'commercial')->exists()) {
            foreach (self::COMMERCIAL_DEFAULTS as $i => [$q, $a]) {
                $this->insertIfMissing('commercial', $q, $a, $i + 1, true);
            }
        }

        // 4) Legacy tables go away.
        foreach (array_keys(self::LEGACY) as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::LEGACY) as $table) {
            if (! Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) {
                    $t->id();
                    $t->string('question');
                    $t->text('answer');
                    $t->unsignedInteger('sort_order')->default(0);
                    $t->boolean('is_visible')->default(true);
                    $t->timestamps();
                });
            }
        }

        foreach (self::LEGACY as $table => $slug) {
            if ($table === 'graduation_faqs') {
                continue;
            }
            foreach (DB::table('faqs')->where('page_slug', $slug)->orderBy('sort_order')->get() as $row) {
                DB::table($table)->insert([
                    'question' => $row->question,
                    'answer' => $row->answer,
                    'sort_order' => $row->sort_order,
                    'is_visible' => $row->is_visible,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function insertIfMissing(string $slug, ?string $question, ?string $answer, int $sortOrder, bool $visible): void
    {
        $question = trim((string) $question);
        $answer = trim((string) $answer);

        if ($question === '' || $answer === '') {
            return;
        }

        $exists = DB::table('faqs')->where('page_slug', $slug)->where('question', $question)->exists();

        if ($exists) {
            return;
        }

        DB::table('faqs')->insert([
            'page_slug' => $slug,
            'question' => $question,
            'answer' => $answer,
            'sort_order' => $sortOrder,
            'is_visible' => $visible,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
