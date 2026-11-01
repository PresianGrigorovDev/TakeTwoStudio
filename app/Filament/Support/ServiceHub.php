<?php

namespace App\Filament\Support;

use App\Filament\Resources;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\ServiceResource\Pages\ManageService;
use App\Models\Faq;
use App\Models\PageContent;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\ServicePromotion;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * One admin page per service (Услуги → Сватби, Балове …) with tabs for everything that
 * belongs to it: packages, extras, photos, FAQ, page texts, promotions, settings.
 * Each tab reuses the existing resource's form + table, so saving, image optimisation
 * and validation stay exactly the same; only the place changes (owner request 2026-11-02).
 */
final class ServiceHub
{
    /** services.slug => resource holding that service's packages. */
    private const PACKAGES = [
        'proms' => Resources\PromPackageResource::class,
        'graduation' => Resources\GraduationPackageResource::class,
        'weddings' => Resources\WeddingPackageResource::class,
        'baptism' => Resources\BaptismPackageResource::class,
        'commercial' => Resources\CommercialPackageResource::class,
        'family' => Resources\FamilyPackageResource::class,
        'portrait' => Resources\PortraitPackageResource::class,
        'automotive' => Resources\AutomotivePackageResource::class,
        'architectural' => Resources\ArchitecturalPackageResource::class,
        'events' => Resources\EventPackageResource::class,
    ];

    /** services.slug => resource with the extras form for that service (Hidden service_id). */
    private const EXTRAS = [
        'proms' => Resources\PromExtraResource::class,
        'weddings' => Resources\WeddingExtraResource::class,
        'baptism' => Resources\BaptismExtraResource::class,
        'commercial' => Resources\CommercialExtraResource::class,
        'family' => Resources\FamilyExtraResource::class,
        'portrait' => Resources\PortraitExtraResource::class,
        'automotive' => Resources\AutomotiveExtraResource::class,
        'architectural' => Resources\ArchitecturalExtraResource::class,
        'events' => Resources\EventExtraResource::class,
    ];

    /** services.slug => [resource, tab label] for the photos shown on the service page. */
    private const PHOTOS = [
        'proms' => [Resources\PromPortfolioPhotoResource::class, 'Снимки'],
        'graduation' => [Resources\GraduationPortfolioPhotoResource::class, 'Снимки'],
        'weddings' => [Resources\WeddingGalleryResource::class, 'Галерии'],
        'baptism' => [Resources\BaptismGalleryResource::class, 'Галерии'],
        'commercial' => [Resources\CommercialPortfolioPhotoResource::class, 'Снимки'],
        'family' => [Resources\FamilyGalleryResource::class, 'Галерии'],
        'portrait' => [Resources\PortraitPortfolioPhotoResource::class, 'Снимки'],
        'automotive' => [Resources\AutomotiveGalleryResource::class, 'Галерии'],
        'architectural' => [Resources\ArchitecturalGalleryResource::class, 'Галерии'],
        'events' => [Resources\EventPortfolioPhotoResource::class, 'Снимки'],
    ];

    /** Sidebar icons per service (Heroicons; the site's FontAwesome icons are not loaded in the admin). */
    private const ICONS = [
        'proms' => 'heroicon-o-academic-cap',
        'graduation' => 'heroicon-o-sparkles',
        'weddings' => 'heroicon-o-heart',
        'baptism' => 'heroicon-o-sun',
        'commercial' => 'heroicon-o-briefcase',
        'family' => 'heroicon-o-user-group',
        'portrait' => 'heroicon-o-user',
        'automotive' => 'heroicon-o-truck',
        'architectural' => 'heroicon-o-building-office-2',
        'events' => 'heroicon-o-musical-note',
    ];

    /**
     * Tabs of a service page, in order.
     *
     * @return array<string,array{label:string,icon:string}>
     */
    public static function tabs(Service $service): array
    {
        $tabs = [];

        if (isset(self::PACKAGES[$service->slug])) {
            $tabs['packages'] = ['label' => 'Пакети и цени', 'icon' => 'heroicon-o-tag'];
        }
        $tabs['extras'] = ['label' => 'Добавки', 'icon' => 'heroicon-o-plus-circle'];
        if (isset(self::PHOTOS[$service->slug])) {
            $tabs['photos'] = ['label' => self::PHOTOS[$service->slug][1], 'icon' => 'heroicon-o-photo'];
        }
        $tabs['faq'] = ['label' => 'Въпроси (FAQ)', 'icon' => 'heroicon-o-question-mark-circle'];
        $tabs['texts'] = ['label' => 'Текстове', 'icon' => 'heroicon-o-document-text'];
        $tabs['promotions'] = ['label' => 'Промоции', 'icon' => 'heroicon-o-receipt-percent'];
        $tabs['settings'] = ['label' => 'Настройки', 'icon' => 'heroicon-o-cog-6-tooth'];

        return $tabs;
    }

    /** Records of a tab (for the table and the tab badge). */
    public static function query(string $section, Service $service): Builder
    {
        return match ($section) {
            'packages' => self::PACKAGES[$service->slug]::getEloquentQuery(),
            'extras' => ServiceExtra::query()->where('service_id', $service->id),
            'photos' => self::PHOTOS[$service->slug][0]::getEloquentQuery(),
            'faq' => Faq::query()->where('page_slug', $service->slug),
            'texts' => PageContent::query()->where('page_slug', $service->slug),
            'promotions' => ServicePromotion::query()->where('service_id', $service->id),
        };
    }

    /** Resource whose form/table the tab reuses. */
    public static function resource(string $section, Service $service): string
    {
        return match ($section) {
            'packages' => self::PACKAGES[$service->slug],
            'extras' => self::EXTRAS[$service->slug] ?? Resources\ServiceExtraResource::class,
            'photos' => self::PHOTOS[$service->slug][0],
            'faq' => Resources\FaqResource::class,
            'texts' => Resources\PageContentResource::class,
            'promotions' => Resources\ServicePromotionResource::class,
        };
    }

    /** Values every record of the tab must have (pre-filled on create and enforced on save). */
    public static function scope(string $section, Service $service): array
    {
        return match ($section) {
            'extras', 'promotions' => ['service_id' => $service->id],
            'faq', 'texts' => ['page_slug' => $service->slug],
            default => [],
        };
    }

    public static function table(string $section, Service $service, Table $table): Table
    {
        $resource = self::resource($section, $service);
        $scope = self::scope($section, $service);
        $form = fn (Form $form) => $resource::form($form);
        $wide = $section === 'photos' || $section === 'texts';

        // Pre-fill: defaults of the form first, then this service's scope on top.
        $mount = function (Form $form) use ($scope) {
            $form->fill();
            if ($scope) {
                $form->fill(array_replace($form->getRawState(), $scope));
            }
        };
        $enforce = fn (array $data) => array_replace($data, $scope);

        $table = $resource::table($table);

        // FAQ / texts are already limited to this page: their "page" filter is noise here.
        if (in_array($section, ['faq', 'texts'], true)) {
            $table->filters([]);
        }

        return $table
            ->query(fn () => self::query($section, $service))
            ->heading(null)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добави')
                    ->model($resource::getModel())
                    ->form($form)
                    ->mountUsing($mount)
                    ->mutateFormDataUsing($enforce)
                    ->slideOver($wide)
                    ->createAnother(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($form)
                    ->mutateFormDataUsing($enforce)
                    ->slideOver($wide),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null)
            ->recordAction('edit')
            ->emptyStateHeading('Още няма записи')
            ->emptyStateDescription('Натиснете „Добави“.');
    }

    /** Sidebar entries: one per service, in the order of Услуги (sort_order). */
    public static function navigationItems(): array
    {
        $current = request()->route('record');

        // Only services that have a page on the site (legacy rows such as "video" stay under Настройки).
        return Service::whereIn('slug', array_keys(self::PACKAGES))
            ->orderByDesc('is_active')->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'slug', 'name_bg', 'is_active'])
            ->values()
            ->map(fn (Service $service, int $i) => NavigationItem::make($service->name_bg.($service->is_active ? '' : ' (скрита)'))
                ->group('Услуги')
                ->icon(self::ICONS[$service->slug] ?? 'heroicon-o-camera')
                ->url(ServiceResource::getUrl('manage', ['record' => $service->slug]))
                ->isActiveWhen(fn () => request()->routeIs(ManageService::getRouteName()) && (string) $current === $service->slug)
                ->sort($i))
            ->all();
    }

}
