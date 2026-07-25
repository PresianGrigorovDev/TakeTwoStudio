<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Всички')
                ->badge(BlogPost::count()),
            'published' => Tab::make('Публикувани (активни)')
                ->modifyQueryUsing(fn (Builder $query) => $query->published())
                ->badge(BlogPost::published()->count())
                ->badgeColor('success'),
            'scheduled' => Tab::make('Насрочени')
                ->modifyQueryUsing(fn (Builder $query) => $query->scheduled())
                ->badge(BlogPost::scheduled()->count())
                ->badgeColor('warning'),
            'draft' => Tab::make('Чернови')
                ->modifyQueryUsing(fn (Builder $query) => $query->draft())
                ->badge(BlogPost::draft()->count())
                ->badgeColor('gray'),
        ];
    }
}
