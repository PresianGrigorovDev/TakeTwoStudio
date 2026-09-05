<?php

namespace App\Support;

use App\Models\PageContent;

/**
 * Owner-editable copy for a page: rows in page_contents keyed by
 * (page_slug, section_slug, field_key). Views pass a sensible default so a
 * page always renders; the owner overrides any text from Filament -> Page Contents.
 */
final class PageText
{
    /** @var array<string,self> */
    private static array $instances = [];

    /** @var array<string,string> "section.field" => content */
    private array $values = [];

    private function __construct(public readonly string $pageSlug)
    {
        $this->values = PageContent::query()
            ->where('page_slug', $pageSlug)
            ->get(['section_slug', 'field_key', 'content_bg'])
            ->mapWithKeys(fn ($row) => [$row->section_slug.'.'.$row->field_key => (string) $row->content_bg])
            ->all();
    }

    public static function for(string $pageSlug): self
    {
        return self::$instances[$pageSlug] ??= new self($pageSlug);
    }

    public function get(string $section, string $field, string $default = ''): string
    {
        $value = trim($this->values[$section.'.'.$field] ?? '');

        return $value !== '' ? $value : $default;
    }

    public function has(string $section, string $field): bool
    {
        return trim($this->values[$section.'.'.$field] ?? '') !== '';
    }
}
