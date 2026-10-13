<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Всички')
                ->badge(Faq::count()),
        ];

        $counts = Faq::query()
            ->selectRaw('page_slug, count(*) as total')
            ->groupBy('page_slug')
            ->pluck('total', 'page_slug');

        foreach (Faq::PAGES as $slug => $label) {
            $count = (int) ($counts[$slug] ?? 0);
            if ($count > 0) {
                $tabs[$slug] = Tab::make($label)
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('page_slug', $slug))
                    ->badge($count);
            }
        }

        return $tabs;
    }
}
