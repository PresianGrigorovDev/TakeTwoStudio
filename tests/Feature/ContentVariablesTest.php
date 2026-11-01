<?php

namespace Tests\Feature;

use App\Filament\Resources\FaqResource;
use App\Filament\Resources\FaqResource\Pages\CreateFaq;
use App\Filament\Resources\WeddingPackageResource;
use App\Filament\Resources\WeddingPackageResource\Pages\CreateWeddingPackage;
use App\Models\Faq;
use App\Models\PromPackage;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Support\ContentVariables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2026-11-02: per-service "Пакети и цени" sections must edit what the site shows, and
 * FAQ texts take prices/delivery days from the database through {{variables}}.
 */
class ContentVariablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PromPackage::query()->delete();
    }

    public function test_wedding_package_section_edits_the_packages_the_site_shows(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $weddings = Service::where('slug', 'weddings')->firstOrFail();

        Livewire::test(CreateWeddingPackage::class)
            ->fillForm(['name_bg' => 'Тестов сватбен пакет', 'price_eur' => 1999, 'display_order' => 9])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = ServicePackage::where('name_bg', 'Тестов сватбен пакет')->sole();
        $this->assertSame($weddings->id, $package->service_id, 'saved as a package of the weddings service');

        $this->get('/weddings')->assertOk()->assertSee('Тестов сватбен пакет');
        $this->get('/ceni')->assertOk()->assertSee('Тестов сватбен пакет');

        $baptismPackage = ServicePackage::create(['service_id' => Service::where('slug', 'baptism')->value('id'), 'name_bg' => 'Само за кръщене', 'price_eur' => 10]);
        $this->get(WeddingPackageResource::getUrl())->assertOk()->assertSee('Тестов сватбен пакет')->assertDontSee($baptismPackage->name_bg);
    }

    public function test_variables_are_filled_from_the_database(): void
    {
        $party = PromPackage::create(['name' => 'Парти', 'price' => 0, 'price_eur' => 130, 'features' => [], 'is_visible' => true, 'sort_order' => 1]);
        PromPackage::create(['name' => 'Лукс', 'price' => 0, 'price_eur' => 150, 'features' => [], 'is_visible' => true, 'sort_order' => 2]);
        Service::where('slug', 'proms')->update(['delivery_days' => 70]);

        $text = 'От {{prom.price.min}} до {{ prom.price.max }}; „{{prom.name.'.$party->id.'}}“ е {{prom.price.'.$party->id.'}}, срок {{delivery.proms}} дни. {{nema.takava}}';

        $this->assertSame('От 130 € до 150 €; „Парти“ е 130 €, срок 70 дни. ', ContentVariables::render($text));
        $this->assertSame(['nema.takava'], ContentVariables::unknownKeys($text));
        $this->assertSame('1 145 €', ContentVariables::price(1145));
        $this->assertSame('99,50 €', ContentVariables::price(99.5));
    }

    public function test_faq_page_schema_and_llms_show_the_current_price(): void
    {
        PromPackage::create(['name' => 'Парти', 'price' => 0, 'price_eur' => 130, 'features' => [], 'is_visible' => true, 'sort_order' => 1]);
        Faq::create(['page_slug' => 'proms', 'question' => 'Колко струва балът през {{season.year}}?', 'answer' => 'Цените започват от {{prom.price.min}} на ученик.', 'sort_order' => 0]);

        $html = $this->get('/proms')->assertOk()->getContent();
        $this->assertStringContainsString('Цените започват от 130 € на ученик.', $html);
        $this->assertStringContainsString('"text":"Цените започват от 130 € на ученик."', $html, 'FAQPage schema gets the rendered text');
        $this->assertStringNotContainsString('{{prom.price.min}}', $html);

        PromPackage::query()->update(['price_eur' => 140]);
        ContentVariables::flush();
        $this->get('/llms-full.txt')->assertOk()->assertSee('Цените започват от 140 € на ученик.');
    }

    public function test_wedding_faq_seeder_uses_only_existing_variables_and_is_idempotent(): void
    {
        Service::where('slug', 'weddings')->update(['delivery_days' => 100]);

        $this->seed(\Database\Seeders\WeddingFaqSeeder::class);
        $this->seed(\Database\Seeders\WeddingFaqSeeder::class);

        $faqs = Faq::where('page_slug', 'weddings')->where('question', 'like', '%')->get();
        $seeded = $faqs->where('question', 'Колко струва сватбен фотограф и видеооператор във Варна?');
        $this->assertCount(1, $seeded, 'running twice does not duplicate');

        foreach ($faqs as $faq) {
            $this->assertSame([], ContentVariables::unknownKeys($faq->question.' '.$faq->answer), $faq->question);
        }

        $html = $this->get('/weddings')->assertOk()->getContent();
        $this->assertStringContainsString('Колко струва сватбен фотограф и видеооператор във Варна?', $html);
        $this->assertStringContainsString('до 100 работни дни', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_faq_form_offers_variables_and_rejects_unknown_ones(): void
    {
        PromPackage::create(['name' => 'Парти', 'price' => 0, 'price_eur' => 130, 'features' => [], 'is_visible' => true, 'sort_order' => 1]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(FaqResource::getUrl('create'))
            ->assertOk()
            ->assertSee('data-tt-variables', false)
            ->assertSee('prom.price.min', false)
            ->assertSee('content-variables.js', false);

        Livewire::test(CreateFaq::class)
            ->fillForm(['page_slug' => 'proms', 'question' => 'Цена?', 'answer' => 'От {{prom.prise.min}}.'])
            ->call('create')
            ->assertHasFormErrors(['answer']);

        Livewire::test(CreateFaq::class)
            ->fillForm(['page_slug' => 'proms', 'question' => 'Цена?', 'answer' => 'От {{prom.price.min}}.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('От {{prom.price.min}}.', Faq::where('question', 'Цена?')->value('answer'), 'the raw variable is stored, not the price');
    }
}
