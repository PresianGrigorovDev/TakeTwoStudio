<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes contradicting FAQ answers found by the live audit on 2026-11-01, using the
 * owner's answers of 2026-11-02:
 *  - no booking deadline ("the earlier, the better"),
 *  - delivery: proms up to 70 working days, pre-prom sessions up to 20, baptism 30,
 *  - drone included in the Парти and Лукс prom packages (as on /ceni),
 *  - "Само Църква" is a calculator scope, not a package.
 * The pre-prom session questions (merged into /proms in September) move to
 * page_slug "graduation" for the future /graduation page.
 *
 * Rows are matched by page_slug + exact question, so FAQs already edited in
 * Filament are left alone. down() restores the previous values.
 */
return new class extends Migration
{
    private const BOOKING = 'Няма краен срок за резервация, но колкото по-рано, толкова по-добре: абитуриентският сезон (края на май и юни) е натоварен и най-търсените дати се запълват първи.';

    /** @return array<int,array{slug:string,question:string,set:array<string,mixed>}> */
    private function changes(): array
    {
        return [
            // Class prom (stays on /proms).
            ['slug' => 'proms', 'question' => 'Кога трябва да запазим дата за бала?', 'set' => ['answer' => self::BOOKING]],
            ['slug' => 'proms', 'question' => 'Предлагате ли дрон кадри за бала?', 'set' => ['answer' => 'Да, дрон кадрите са включени в пакетите Парти и Лукс. Въздушните кадри добавят кинематографична визия към видеото от бала.']],
            ['slug' => 'proms', 'question' => 'Кога получаваме готовите снимки и видео?', 'set' => ['answer' => 'Обработените снимки и видео от бала се предават до 70 работни дни след събитието. При желание за по-бърза доставка предлагаме експресна обработка.']],

            // Pre-prom session (moves to /graduation).
            ['slug' => 'proms', 'question' => 'Кога трябва да резервирам?', 'set' => ['page_slug' => 'graduation', 'answer' => 'Няма краен срок, но колкото по-рано, толкова по-добре: пиковият период е май–юни и най-търсените часове се запълват първи.']],
            ['slug' => 'proms', 'question' => 'Колко снимки ще получим и кога?', 'set' => ['page_slug' => 'graduation', 'answer' => 'Броят снимки зависи от пакета (25–80+). Всички снимки са индивидуално обработени – цветово балансирани и ретуширани. Получавате онлайн галерия до 20 работни дни след сесията, откъдето можете да свалите всичко.']],
            ['slug' => 'proms', 'question' => 'Колко трае фотосесията?', 'set' => ['page_slug' => 'graduation']],
            ['slug' => 'proms', 'question' => 'Снимате ли само абитуриента или цялото семейство?', 'set' => ['page_slug' => 'graduation']],
            ['slug' => 'proms', 'question' => 'Може ли сесията да е на открито?', 'set' => ['page_slug' => 'graduation']],

            // Baptism: one delivery answer (30 days), no duplicate, no "package" called Само Църква.
            ['slug' => 'baptism', 'question' => 'Колко време отнема заснемането на Свето Кръщение?', 'set' => ['answer' => 'Самият църковен ритуал обикновено трае около 40–50 минути. Идваме 15–20 минути по-рано, за да снимаме детайлите и гостите, а след ритуала правим семейна фотосесия пред църквата. Когато заснемаме само църквата, ангажиментът е общо около 1,5 часа; празненството в ресторанта се добавя като опция.']],
            ['slug' => 'baptism', 'question' => 'Колко време трае самото кръщене?', 'set' => ['is_visible' => false]],
            ['slug' => 'baptism', 'question' => 'Кога получаваме готовите снимки и видео?', 'set' => ['is_visible' => false]],
        ];
    }

    public function up(): void
    {
        foreach ($this->changes() as $change) {
            DB::table('faqs')
                ->where('page_slug', $change['slug'])
                ->where('question', $change['question'])
                ->update($change['set'] + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $old = [
            'Кога трябва да запазим дата за бала?' => ['answer' => 'Препоръчваме да запазите дата минимум 7–8 месеца предварително. Абитуриентският сезон е натоварен и местата се запълват бързо.'],
            'Предлагате ли дрон кадри за бала?' => ['answer' => 'Да, дрон заснемането е налично като екстра към всеки пакет. Въздушните кадри добавят кинематографична визия към видеото от вашия специален ден.'],
            'Кога получаваме готовите снимки и видео?' => ['answer' => 'Обработените материали се предават до 50 работни дни след събитието. При желание за по-бърза доставка предлагаме експресна обработка.'],
            'Кога трябва да резервирам?' => ['page_slug' => 'proms', 'answer' => 'Препоръчваме резервация поне 3–4 седмици преди бала. Пиковият период е май–юни, когато датите се запълват бързо. Колкото по-рано резервирате, толкова по-голям избор на часове и дати имате.'],
            'Колко снимки ще получим и кога?' => ['page_slug' => 'proms', 'answer' => 'Броят снимки зависи от пакета (25–80+). Всички снимки са индивидуално обработени — цветово балансирани и ретушираните. Получавате достъп до онлайн галерия в рамките на 5 работни дни след сесията, откъдето можете да свалите всичко.'],
            'Колко трае фотосесията?' => ['page_slug' => 'proms'],
            'Снимате ли само абитуриента или цялото семейство?' => ['page_slug' => 'proms'],
            'Може ли сесията да е на открито?' => ['page_slug' => 'proms'],
            'Колко време отнема заснемането на Свето Кръщение?' => ['answer' => 'Самият църковен ритуал обикновено трае около 40-50 минути. Ние винаги сме там 15-20 минути по-рано, за да снимаме детайлите и гостите. След ритуала отделяме време за семейна фотосесия пред църквата. Общо ангажиментът е около 1.5 часа (за пакет "Само Църква").'],
            'Колко време трае самото кръщене?' => ['is_visible' => true],
        ];

        foreach ($this->changes() as $change) {
            $slug = $change['set']['page_slug'] ?? $change['slug'];
            $restore = $old[$change['question']] ?? null;

            // The baptism delivery question shares its text with the prom one; restore by slug.
            if ($change['slug'] === 'baptism' && $change['question'] === 'Кога получаваме готовите снимки и видео?') {
                $restore = ['is_visible' => true];
            }

            if ($restore) {
                DB::table('faqs')->where('page_slug', $slug)->where('question', $change['question'])->update($restore);
            }
        }
    }
};
