<?php

namespace Tests\Feature;

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_and_view_faqs_in_filament(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Faq::create([
            'page_slug' => 'proms',
            'question' => 'Колко струва фотограф за бал?',
            'answer' => 'От 100 евро на ученик.',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        Faq::create([
            'page_slug' => 'weddings',
            'question' => 'Кога получаваме сватбените снимки?',
            'answer' => 'До 30 работни дни след сватбата.',
            'sort_order' => 2,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($admin)->get(FaqResource::getUrl());
        $response->assertOk();
        $response->assertSee('Колко струва фотограф за бал?');
        $response->assertSee('Кога получаваме сватбените снимки?');
        $response->assertSee('Всички');
        $response->assertSee('Абитуриентски балове');
        $response->assertSee('Сватби');
    }

    public function test_faq_pagination_does_not_conflict_with_for_page_scope(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $initialCount = Faq::count();

        // Create 25 FAQs across different pages
        for ($i = 1; $i <= 25; $i++) {
            Faq::create([
                'page_slug' => $i % 2 === 0 ? 'weddings' : 'proms',
                'question' => "Тестов въпрос номер {$i}?",
                'answer' => "Тестов отговор номер {$i}.",
                'sort_order' => $i,
                'is_visible' => true,
            ]);
        }

        // Querying with pagination should return records on page 1 without filtering page_slug = '1'
        $paginated = Faq::paginate(10);
        $this->assertCount(10, $paginated->items());
        $this->assertEquals($initialCount + 25, $paginated->total());

        // Admin Filament page 1 loads records
        $response = $this->actingAs($admin)->get(FaqResource::getUrl());
        $response->assertOk();
        $response->assertDontSee('No Въпроси и отговори');
        $response->assertDontSee('Showing 0 to 0 of');
    }
}
