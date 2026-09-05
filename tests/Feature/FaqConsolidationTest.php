<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FaqConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_tables_are_gone_and_faqs_live_in_one_table(): void
    {
        foreach (['wedding_faqs', 'prom_faqs', 'baptism_faqs', 'commercial_faqs', 'graduation_faqs'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "$table should have been merged into faqs");
        }

        $this->assertTrue(Schema::hasColumns('faqs', ['page_slug', 'question', 'answer', 'sort_order', 'is_visible']));
        $this->assertFalse(Schema::hasColumn('faqs', 'question_bg'));

        // The commercial FAQs that used to be hard-coded in the Blade view were migrated into the table.
        $this->assertCount(4, Faq::forPageVisible('commercial'));
    }

    public function test_service_pages_render_their_faqs_from_the_table(): void
    {
        $this->seed();

        $this->assertCount(4, Faq::forPageVisible('weddings'));

        Faq::create(['page_slug' => 'family', 'question' => 'Колко струва семейна фотосесия във Варна?', 'answer' => 'От 120 € за едночасова сесия на открито.', 'sort_order' => 1]);
        Faq::create(['page_slug' => 'family', 'question' => 'Скрит въпрос', 'answer' => 'Не трябва да се показва.', 'sort_order' => 2, 'is_visible' => false]);

        $response = $this->get('/family')->assertOk();
        $response->assertSee('Колко струва семейна фотосесия във Варна?');
        $response->assertDontSee('Скрит въпрос');
        $response->assertSee('"FAQPage"', false);

        $this->get('/commercial')->assertOk()->assertSee('Каква е цената за продуктова фотография?');
    }
}
