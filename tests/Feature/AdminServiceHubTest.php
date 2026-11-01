<?php

namespace Tests\Feature;

use App\Filament\Resources\ServiceResource;
use App\Filament\Support\ServiceHub;
use App\Livewire\ServiceHubSettings;
use App\Livewire\ServiceHubTable;
use App\Models\Faq;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin menu restructure (2026-11-02): Работа / Услуги / Съдържание / Маркетинг / Настройки,
 * and one page per service with tabs for everything that belongs to it.
 */
class AdminServiceHubTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = ['proms', 'graduation', 'weddings', 'baptism', 'commercial', 'family', 'portrait', 'automotive', 'architectural', 'events'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        foreach (self::SLUGS as $i => $slug) {
            Service::firstOrCreate(['slug' => $slug], ['name_bg' => 'Услуга '.$slug, 'is_active' => true, 'sort_order' => 50 + $i]);
        }

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function service(string $slug): Service
    {
        return Service::where('slug', $slug)->firstOrFail();
    }

    public function test_sidebar_has_work_first_one_entry_per_service_and_no_per_service_sections(): void
    {
        $html = $this->get('/admin')->assertOk()->getContent();

        $positions = array_map(fn ($group) => mb_strpos($html, '>'.$group.'<') ?: mb_strpos($html, $group), ['Работа', 'Услуги', 'Съдържание', 'Маркетинг', 'Настройки']);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'groups in the agreed order');

        foreach (self::SLUGS as $slug) {
            $this->assertStringContainsString(ServiceResource::getUrl('manage', ['record' => $slug]), $html, "sidebar links the $slug page");
        }

        // The old per-service sections are reachable only from the service pages.
        $this->assertStringNotContainsString('/admin/wedding-packages"', $html);
        $this->assertStringNotContainsString('/admin/prom-extras"', $html);
        $this->assertStringNotContainsString('/admin/wedding-galleries"', $html);
    }

    public function test_service_page_shows_its_tabs(): void
    {
        $this->get(ServiceResource::getUrl('manage', ['record' => 'weddings']))
            ->assertOk()
            ->assertSee('Пакети и цени')
            ->assertSee('Добавки')
            ->assertSee('Галерии')
            ->assertSee('Въпроси (FAQ)')
            ->assertSee('Текстове')
            ->assertSee('Промоции')
            ->assertSee('Настройки')
            ->assertSee('Виж на сайта');
    }

    public function test_every_tab_of_every_service_renders(): void
    {
        foreach (self::SLUGS as $slug) {
            $service = $this->service($slug);
            foreach (array_keys(ServiceHub::tabs($service)) as $section) {
                if ($section === 'settings') {
                    Livewire::test(ServiceHubSettings::class, ['service' => $service])->assertSuccessful();

                    continue;
                }
                Livewire::test(ServiceHubTable::class, ['serviceId' => $service->id, 'section' => $section])->assertSuccessful();
            }
        }
    }

    public function test_packages_tab_lists_and_creates_only_this_services_packages(): void
    {
        $weddings = $this->service('weddings');
        $baptismPackage = ServicePackage::create(['service_id' => $this->service('baptism')->id, 'name_bg' => 'Кръщене пакет', 'price_eur' => 120]);

        Livewire::test(ServiceHubTable::class, ['serviceId' => $weddings->id, 'section' => 'packages'])
            ->assertCanSeeTableRecords($weddings->packages)
            ->assertCanNotSeeTableRecords([$baptismPackage])
            ->callTableAction('create', data: ['name_bg' => 'Нов сватбен', 'price_eur' => 1500])
            ->assertHasNoTableActionErrors();

        $this->assertSame($weddings->id, ServicePackage::where('name_bg', 'Нов сватбен')->value('service_id'));
    }

    public function test_faq_and_extras_tabs_always_save_to_this_service(): void
    {
        $proms = $this->service('proms');
        Livewire::test(ServiceHubTable::class, ['serviceId' => $proms->id, 'section' => 'faq'])
            ->callTableAction('create', data: ['page_slug' => 'weddings', 'question' => 'Колко струва балът?', 'answer' => 'От 130 €.'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('proms', Faq::where('question', 'Колко струва балът?')->value('page_slug'), 'scope wins over the form');

        $graduation = $this->service('graduation');
        Livewire::test(ServiceHubTable::class, ['serviceId' => $graduation->id, 'section' => 'extras'])
            ->callTableAction('create', data: ['service_id' => $proms->id, 'label_bg' => 'Допълнителен час', 'price_eur' => 40, 'input_type' => 'checkbox'])
            ->assertHasNoTableActionErrors();
        $this->assertSame($graduation->id, ServiceExtra::where('label_bg', 'Допълнителен час')->value('service_id'));
    }

    public function test_settings_tab_saves_the_service(): void
    {
        $service = $this->service('weddings');

        Livewire::test(ServiceHubSettings::class, ['service' => $service])
            ->fillForm(['delivery_days' => 95])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(95, (int) $service->fresh()->delivery_days);
    }
}
