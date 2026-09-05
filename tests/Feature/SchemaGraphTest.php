<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaGraphTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @return array<int,array<string,mixed>> the @graph of the single JSON-LD script on the page */
    private function graph(string $path): array
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertCount(1, $m[1], "$path must emit exactly one JSON-LD script");

        $data = json_decode($m[1][0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        return $data['@graph'];
    }

    private function node(array $graph, string $type): ?array
    {
        foreach ($graph as $node) {
            $types = (array) ($node['@type'] ?? []);
            if (in_array($type, $types, true)) {
                return $node;
            }
        }

        return null;
    }

    public function test_home_has_a_linked_entity_graph_without_self_serving_ratings(): void
    {
        $graph = $this->graph('/');

        $org = $this->node($graph, 'Organization');
        $biz = $this->node($graph, 'LocalBusiness');
        $site = $this->node($graph, 'WebSite');
        $page = $this->node($graph, 'WebPage');

        $this->assertNotNull($org);
        $this->assertNotNull($biz);
        $this->assertNotNull($site);
        $this->assertNotNull($page);

        $this->assertSame($org['@id'], $biz['parentOrganization']['@id']);
        $this->assertSame($org['@id'], $site['publisher']['@id']);
        $this->assertSame($site['@id'], $page['isPartOf']['@id']);
        $this->assertSame('+359886190124', $biz['telephone']);
        $this->assertSame('ж.к. Възраждане IV 1603', $biz['address']['streetAddress']);
        $this->assertContains('https://www.instagram.com/taketwostudio1603', $org['sameAs']);
        $this->assertSame(TeamMember::where('is_active', true)->count(), count(array_filter($graph, fn ($n) => ($n['@type'] ?? null) === 'Person')));
        $this->assertStringNotContainsString('aggregateRating', json_encode($graph));
        $this->assertArrayNotHasKey('breadcrumb', $page, 'home has no breadcrumb trail');
    }

    public function test_service_page_has_service_offers_faq_and_breadcrumbs(): void
    {
        $graph = $this->graph('/weddings');

        $service = $this->node($graph, 'Service');
        $this->assertNotNull($service);
        $this->assertSame(url('/weddings'), $service['url']);
        $this->assertSame($this->node($graph, 'LocalBusiness')['@id'], $service['provider']['@id']);
        $this->assertNotEmpty($service['offers'], 'seeded wedding packages must become Offers');
        $this->assertSame('EUR', $service['offers'][0]['priceCurrency']);

        $page = $this->node($graph, 'FAQPage');
        $this->assertNotNull($page, 'seeded wedding FAQs must mark the WebPage as FAQPage');
        $this->assertCount(4, $page['mainEntity']);

        $crumbs = $this->node($graph, 'BreadcrumbList');
        $this->assertNotNull($crumbs);
        $this->assertCount(2, $crumbs['itemListElement']);
        $this->assertSame('Начало', $crumbs['itemListElement'][0]['name']);
        $this->assertSame($crumbs['@id'], $page['breadcrumb']['@id']);

        $this->get('/weddings')->assertSee('breadcrumb-bar', false);
    }

    public function test_blog_post_has_blogposting_with_person_author_when_assigned(): void
    {
        $category = BlogCategory::create(['name' => 'Сватбени съвети', 'slug' => 'wedding-tips', 'is_visible' => true]);
        $author = TeamMember::where('is_active', true)->first();

        $post = BlogPost::create([
            'title' => 'Колко струва сватбен фотограф във Варна',
            'slug' => 'kolko-struva-svatben-fotograf-varna',
            'excerpt' => 'Реални цени и какво включват пакетите.',
            'body' => '<p>Цените за сватбена фотография във Варна започват от 890 евро.</p>',
            'cover_image' => 'css/img/social-share-cover.jpg',
            'category_id' => $category->id,
            'author_team_member_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $graph = $this->graph('/blog/'.$post->slug);

        $article = $this->node($graph, 'BlogPosting');
        $this->assertNotNull($article);
        $this->assertSame($post->title, $article['headline']);
        $this->assertStringContainsString('#person-'.$author->id, $article['author']['@id']);
        $this->assertNotNull($this->node($graph, 'Person'));
        $this->assertSame('Сватбени съвети', $article['articleSection']);

        $crumbs = $this->node($graph, 'BreadcrumbList');
        $this->assertSame(['Начало', 'Блог', 'Сватбени съвети', $post->title], array_column($crumbs['itemListElement'], 'name'));

        $page = $this->node($graph, 'WebPage');
        $this->assertArrayHasKey('datePublished', $page);
    }

    public function test_prices_page_has_an_offer_catalog_with_eur_offers(): void
    {
        $graph = $this->graph('/ceni');

        $catalog = $this->node($graph, 'OfferCatalog');
        $this->assertNotNull($catalog);
        $offers = collect($catalog['itemListElement'])->flatMap(fn ($g) => $g['itemListElement']);
        $this->assertTrue($offers->isNotEmpty(), 'seeded packages must appear as Offers');
        $this->assertSame('EUR', $offers->first()['priceCurrency']);
        $this->assertSame(['Начало', 'Цени'], array_column($this->node($graph, 'BreadcrumbList')['itemListElement'], 'name'));
    }

    public function test_about_and_contact_pages_declare_their_page_types(): void
    {
        $about = $this->node($this->graph('/za-nas'), 'AboutPage');
        $this->assertNotNull($about);
        $this->assertNotNull($this->node($this->graph('/za-nas'), 'Person'));

        $contact = $this->node($this->graph('/kontakti'), 'ContactPage');
        $this->assertNotNull($contact);
    }

    public function test_prom_guide_has_faq_and_three_level_breadcrumbs(): void
    {
        $graph = $this->graph('/abiturientski-bal-varna');

        $this->assertNotNull($this->node($graph, 'FAQPage'), 'seeded guide FAQs must mark the page as FAQPage');
        $this->assertSame(['Начало', 'Абитуриентски балове'], array_slice(array_column($this->node($graph, 'BreadcrumbList')['itemListElement'], 'name'), 0, 2));
        $this->get('/proms')->assertOk()->assertSee('abiturientski-bal-varna');
    }
}
