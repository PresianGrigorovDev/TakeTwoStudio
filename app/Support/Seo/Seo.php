<?php

namespace App\Support\Seo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Per-request bag of SEO facts that controllers/views fill in and the layout
 * turns into ONE schema.org @graph (partials/schema-graph.blade.php) and the
 * visible breadcrumb bar (partials/breadcrumbs.blade.php).
 *
 * Bound as a scoped singleton in AppServiceProvider.
 */
final class Seo
{
    /** @var array<int,array<string,mixed>> */
    private array $nodes = [];

    /** @var array<int,array{name:string,url:?string}> */
    private array $breadcrumbs = [];

    private ?Collection $faqs = null;

    private ?Carbon $datePublished = null;

    private ?Carbon $dateModified = null;

    private ?string $pageType = null;

    /** Site root without trailing slash, e.g. https://taketwostudio1603.com */
    public static function root(): string
    {
        return rtrim(url('/'), '/');
    }

    /** Stable entity id, e.g. https://taketwostudio1603.com/#organization */
    public static function rootId(string $fragment): string
    {
        return self::root().'/#'.$fragment;
    }

    public function addNode(array $node): self
    {
        $this->nodes[] = $node;

        return $this;
    }

    /** @return array<int,array<string,mixed>> */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /** @return array<int,array<string,mixed>> nodes of a given @type */
    public function nodesOfType(string $type): array
    {
        return array_values(array_filter($this->nodes, fn (array $n) => ($n['@type'] ?? null) === $type || (is_array($n['@type'] ?? null) && in_array($type, $n['@type'], true))));
    }

    /** @param array<int,array{name:string,url:?string}> $items */
    public function setBreadcrumbs(array $items): self
    {
        $this->breadcrumbs = array_values($items);

        return $this;
    }

    /** Explicit breadcrumbs if set, otherwise derived from the current route. */
    public function breadcrumbs(): array
    {
        return $this->breadcrumbs !== [] ? $this->breadcrumbs : Breadcrumbs::guess();
    }

    /** @param iterable<object|array> $faqs objects/arrays with question + answer */
    public function setFaqs(iterable $faqs): self
    {
        $this->faqs = collect($faqs)
            ->map(function ($faq) {
                $faq = is_array($faq) ? (object) $faq : $faq;
                $q = trim((string) ($faq->question ?? $faq->question_bg ?? $faq->q ?? ''));
                $a = trim((string) ($faq->answer ?? $faq->answer_bg ?? $faq->a ?? ''));

                return $q !== '' && $a !== '' ? ['question' => $q, 'answer' => $a] : null;
            })
            ->filter()
            ->values();

        return $this;
    }

    public function faqs(): Collection
    {
        return $this->faqs ?? collect();
    }

    public function setDates(?Carbon $published, ?Carbon $modified): self
    {
        $this->datePublished = $published;
        $this->dateModified = $modified;

        return $this;
    }

    public function datePublished(): ?Carbon
    {
        return $this->datePublished;
    }

    public function dateModified(): ?Carbon
    {
        return $this->dateModified;
    }

    /** Extra schema.org type for the WebPage node, e.g. "CollectionPage", "ContactPage". */
    public function setPageType(?string $type): self
    {
        $this->pageType = $type;

        return $this;
    }

    public function pageType(): ?string
    {
        return $this->pageType;
    }
}
