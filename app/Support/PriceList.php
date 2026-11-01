<?php

namespace App\Support;

use App\Models\ArchitecturalPackage;
use App\Models\AutomotivePackage;
use App\Models\CommercialPackage;
use App\Models\EventPackage;
use App\Models\FamilyPackage;
use App\Models\GraduationPackage;
use App\Models\PortraitPackage;
use App\Models\PromPackage;
use App\Models\Service;
use Illuminate\Support\Carbon;

/**
 * Every visible package of every service, normalised to EUR rows. Single
 * source for /ceni and /llms-full.txt so the two can never disagree.
 */
final class PriceList
{
    /** @return array<int,array{id:string,title:string,url:string,unit:string,note:string,models:array<int,class-string|array>}> */
    private static function definitions(): array
    {
        return [
            ['id' => 'abiturienti', 'title' => 'Абитуриентски балове', 'url' => url('/proms'), 'unit' => 'на ученик', 'note' => 'Цената е за целия клас, на ученик: канене на класния, изпращане, балната вечер и фотосесиите на класа.', 'models' => [PromPackage::class]],
            ['id' => 'predbalni', 'title' => 'Пред-бални и абсолвентски фотосесии', 'url' => url('/proms'), 'unit' => '', 'note' => 'Индивидуални и семейни фотосесии в деня на бала или на избрана локация във Варна.', 'models' => [GraduationPackage::class]],
            ['id' => 'svatbi', 'title' => 'Сватби – фотография и видео', 'url' => url('/weddings'), 'unit' => '', 'note' => 'Един екип за снимки и 4K филм. Комбинациите и екстрите се изчисляват в сватбения калкулатор.', 'models' => [['service', 'weddings']]],
            ['id' => 'krashtene', 'title' => 'Кръщене', 'url' => url('/baptism'), 'unit' => '', 'note' => 'Ритуалът в църквата, семейна фотосесия и празненството.', 'models' => [['service', 'baptism']]],
            ['id' => 'semeini', 'title' => 'Семейни фотосесии', 'url' => url('/family'), 'unit' => '', 'note' => '', 'models' => [FamilyPackage::class]],
            ['id' => 'portret', 'title' => 'Портретна фотография', 'url' => url('/portrait'), 'unit' => '', 'note' => '', 'models' => [PortraitPackage::class]],
            ['id' => 'biznes', 'title' => 'Рекламна, продуктова и бизнес фотография', 'url' => url('/commercial'), 'unit' => '', 'note' => 'Продуктова фотография, рекламни видеа, дрон и корпоративно съдържание. За по-големи проекти изготвяме индивидуална оферта.', 'models' => [['service', 'commercial'], CommercialPackage::class]],
            ['id' => 'avtomobilna', 'title' => 'Автомобилна фотография', 'url' => url('/automotive'), 'unit' => '', 'note' => '', 'models' => [AutomotivePackage::class]],
            ['id' => 'arhitekturna', 'title' => 'Архитектурна и интериорна фотография', 'url' => url('/architectural'), 'unit' => '', 'note' => '', 'models' => [ArchitecturalPackage::class]],
            ['id' => 'sabitiya', 'title' => 'Събития', 'url' => url('/events'), 'unit' => '', 'note' => '', 'models' => [EventPackage::class]],
        ];
    }

    /**
     * @return array{groups: array<int,array<string,mixed>>, updatedAt: ?Carbon}
     */
    public static function build(): array
    {
        $groups = [];
        $updatedAt = null;

        foreach (self::definitions() as $group) {
            $packages = collect();
            $extras = collect();

            foreach ($group['models'] as $source) {
                if (is_array($source)) {
                    $service = Service::where('slug', $source[1])->with(['packages' => fn ($q) => $q->orderBy('price_eur'), 'extras' => fn ($q) => $q->orderBy('group_name_bg')->orderBy('price_eur')])->first();
                    if ($service) {
                        $packages = $packages->merge($service->packages);
                        $extras = $extras->merge($service->extras);
                    }
                } else {
                    $packages = $packages->merge($source::where('is_visible', true)->orderBy('sort_order')->get());
                }
            }

            $rows = $packages->map(fn ($p) => self::normalizePackage($p))->filter()->values();

            if ($rows->isEmpty()) {
                continue;
            }

            $groupUpdated = $packages->merge($extras)->max('updated_at');
            $updatedAt = max($updatedAt, $groupUpdated ? Carbon::parse($groupUpdated) : null);

            $groups[] = $group + [
                'packages' => $rows,
                'extras' => $extras->filter(fn ($e) => (float) ($e->price_eur ?? 0) > 0)->map(fn ($e) => [
                    'name' => $e->label_bg ?? $e->name_bg ?? '',
                    'group' => $e->group_name_bg ?? '',
                    'price' => (float) $e->price_eur,
                ])->values(),
            ];
        }

        return ['groups' => $groups, 'updatedAt' => $updatedAt];
    }

    /** @return array{name:string,price:float,description:string,features:array<int,string>,featured:bool}|null */
    private static function normalizePackage($package): ?array
    {
        $price = (float) ($package->price_eur ?? 0);
        $name = trim((string) ($package->name_bg ?? $package->name ?? ''));

        if ($price <= 0 || $name === '') {
            return null;
        }

        $features = $package->features ?? [];
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : preg_split('/\r?\n/', $features);
        }

        // Note: never ->map('trim'): Collection::map passes the key as trim()'s
        // character mask, which stripped digits ("25 снимки" -> "5 снимки").
        return [
            'name' => $name,
            'price' => $price,
            'description' => trim(strip_tags((string) ($package->description_bg ?? $package->description ?? ''))),
            'features' => collect($features)->map(fn ($f) => is_array($f) ? implode(' ', array_map('strval', $f)) : (string) $f)->map(fn (string $f) => trim($f))->filter()->values()->all(),
            'featured' => (bool) ($package->is_featured ?? $package->is_default ?? false),
        ];
    }
}
