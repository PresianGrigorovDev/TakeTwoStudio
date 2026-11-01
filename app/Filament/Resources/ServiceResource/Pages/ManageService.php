<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Filament\Support\ServiceHub;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

/** Everything about one service on one page (sidebar: Услуги → <service>). */
class ManageService extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ServiceResource::class;

    protected static string $view = 'filament.resources.service-resource.pages.manage-service';

    #[Url]
    public string $tab = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (! array_key_exists($this->tab, $this->getTabs())) {
            $this->tab = array_key_first($this->getTabs());
        }
    }

    /** @return array<string,array{label:string,icon:string,badge:?int}> */
    public function getTabs(): array
    {
        $tabs = ServiceHub::tabs($this->record);

        foreach ($tabs as $key => $tab) {
            $tabs[$key]['badge'] = $key === 'settings' ? null : ServiceHub::query($key, $this->record)->count();
        }

        return $tabs;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name_bg;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')
                ->label('Виж на сайта')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(url($this->record->slug))
                ->openUrlInNewTab(),
        ];
    }
}
