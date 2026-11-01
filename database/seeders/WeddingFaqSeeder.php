<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

/**
 * Wedding FAQs (2026-11-02), answer-first and quotable by AI assistants. Prices,
 * delivery days and the phone are {{variables}}, so they follow the database.
 * Extras and packages are looked up by name, so the right {{…id}} is used on every
 * database; a sentence whose extra does not exist is left out.
 *
 * Run once:  php artisan db:seed --class=WeddingFaqSeeder --force
 * Safe to re-run: rows are matched on page_slug + question (edited answers stay).
 */
class WeddingFaqSeeder extends Seeder
{
    private ?Service $weddings = null;

    public function run(): void
    {
        $this->weddings = Service::where('slug', 'weddings')->first();

        $twoPhotographers = $this->packagePrice('Фото заснемане: Двама фотографи');
        $drone = $this->extraPrice('Дрон');
        $longFilm = $this->extraPrice('Филм до 120 мин');
        $reels = $this->extraPrice('Видео визитки (Shorts/Reels)');
        $afterSession = $this->extraPrice('Следсватбена фотосесия');
        $box = $this->extraPrice('Луксозна дървена кутия с флашка');
        $fullPack = $this->extraPrice('Пълен пакет: Кутия + Флашка + Албум');
        $delivery = $this->weddings?->delivery_days ? '{{delivery.weddings}}' : null;

        $faqs = array_filter([
            ['Колко струва сватбен фотограф и видеооператор във Варна?',
                'Сватбените пакети на Take Two Studio 1603 започват от {{wedding.price.min}} за един фотограф или един видеооператор и стигат до {{wedding.price.max}} за двама. Снимките и видеото могат да се комбинират, а екстри като дрон, 6K или албум се добавят по желание. Точната цена виждате веднага в калкулатора на страницата.'],
            ['Снимате ли и снимки, и видео от един екип?',
                'Да. Снимките и 4K филмът се правят от един екип, който работи заедно през целия ден, затова кадрите и видеото разказват една и съща история и не си пречат. Можете да изберете само фото, само видео или двете заедно.'],
            ['Един или двама фотографи да изберем?',
                'С един фотограф е достатъчно за по-малка сватба с един основен локал. С двама се заснемат едновременно подготовката на булката и на младоженеца, гостите и реакциите, без пропуснати моменти.'
                .($twoPhotographers ? " Пакетът с двама фотографи е {$twoPhotographers}." : '')],
            $drone ? ['Включен ли е дрон?',
                "Дронът се добавя към всеки сватбен пакет за {$drone}. Въздушните кадри се правят, когато времето и мястото позволяват безопасен и разрешен полет, и са особено красиви на сватби край морето."] : null,
            $delivery ? ['Кога получаваме снимките и филма?',
                "Готовите снимки и сватбеният филм се предават до {$delivery} работни дни след сватбата. Всеки кадър се обработва ръчно, а филмът се монтира с музика и звук от деня."] : null,
            ['Колко предварително да запазим дата?',
                'Няма краен срок, но колкото по-рано, толкова по-добре: най-търсените съботи от май до септември се запълват първи. Проверете свободните дати в календара на сайта или се обадете на {{site.phone}}.'],
            ['Снимате ли сватби извън Варна?',
                'Да. Базирани сме във Варна и снимаме в Златни пясъци, Св. Св. Константин и Елена, Балчик, Каварна, Бяла, Добрич и Шумен, както и в цяла България.'],
            $longFilm ? ['Колко дълъг е сватбеният филм?',
                "Сватбеният филм включва най-важните моменти от деня, монтирани с музика и звук от сватбата. Ако искате по-подробен филм, той може да е до 120 минути срещу {$longFilm}."] : null,
            $reels ? ['Правите ли кратки видеа за Instagram и TikTok?',
                "Да. По желание монтираме вертикални видео визитки (Shorts/Reels) от сватбата, готови за споделяне в социалните мрежи. Цената е {$reels}."] : null,
            $afterSession ? ['Предлагате ли следсватбена фотосесия?',
                "Да. Следсватбената фотосесия е отделна, спокойна сесия на избрана от вас локация, например на плажа или в Морската градина във Варна, без натоварения график на сватбения ден. Цената е {$afterSession}."] : null,
            ['Как получаваме снимките и видеото?',
                'Снимките и филма получавате в дигитален вид.'
                .($box && $fullPack ? " По желание и в луксозна дървена кутия с флашка ({$box}) или в пълен пакет с кутия, флашка и албум ({$fullPack})." : '')],
            ['Как се запазва дата за сватба?',
                'Изберете пакет в калкулатора на страницата и изпратете заявка или се обадете на {{site.phone}}. Датата се потвърждава с договор.'],
        ]);

        $offset = (int) Faq::forPageSlug('weddings')->max('sort_order');
        $created = 0;

        foreach (array_values($faqs) as $i => [$question, $answer]) {
            $faq = Faq::firstOrCreate(
                ['page_slug' => 'weddings', 'question' => $question],
                ['answer' => $answer, 'sort_order' => $offset + $i + 1, 'is_visible' => true],
            );
            $created += (int) $faq->wasRecentlyCreated;
        }

        $this->command?->info("Сватбени FAQ: добавени {$created}, вече съществуващи ".(count($faqs) - $created).'.');
    }

    /** "{{extra.price.N}}" of a wedding extra, or null when it does not exist. */
    private function extraPrice(string $label): ?string
    {
        $id = $this->weddings ? ServiceExtra::where('service_id', $this->weddings->id)->where('label_bg', $label)->where('price_eur', '>', 0)->value('id') : null;

        return $id ? "{{extra.price.{$id}}}" : null;
    }

    /** "{{wedding.price.N}}" of a wedding package, or null when it does not exist. */
    private function packagePrice(string $name): ?string
    {
        $id = $this->weddings ? ServicePackage::where('service_id', $this->weddings->id)->where('name_bg', $name)->where('price_eur', '>', 0)->value('id') : null;

        return $id ? "{{wedding.price.{$id}}}" : null;
    }
}
