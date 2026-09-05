<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public static function servicePages(): array
    {
        return [['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'], ['/portrait'], ['/automotive'], ['/architectural'], ['/events'], ['/'], ['/ceni'], ['/za-nas'], ['/kontakti'], ['/abiturientski-bal-varna']];
    }

    #[DataProvider('servicePages')]
    public function test_title_and_description_fit_the_serp_and_h1_names_the_city(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $title);
        preg_match('#<meta name="description" content="(.*?)">#s', $html, $description);
        preg_match_all('#<h1[^>]*>(.*?)</h1>#s', $html, $h1);

        $this->assertLessThanOrEqual(60, mb_strlen(trim($title[1])), "$path title too long: {$title[1]}");
        $this->assertLessThanOrEqual(155, mb_strlen(trim($description[1])), "$path description too long");
        $this->assertCount(1, $h1[0], "$path must have exactly one <h1>");
        $this->assertStringContainsString('Варна', strip_tags($h1[1][0]), "$path H1 should name the city");
    }

    public function test_prom_guide_title_and_capsule_carry_a_real_season_year(): void
    {
        $this->seed();
        $year = \App\Support\Seo\PromSeason::year();

        $this->assertGreaterThanOrEqual(2026, $year);
        $this->get('/abiturientski-bal-varna')->assertOk()->assertSee("Абитуриентски бал Варна {$year}")->assertDontSee('Варна 0');
        $this->get('/proms')->assertOk()->assertSee("випуск {$year}");
    }
}
