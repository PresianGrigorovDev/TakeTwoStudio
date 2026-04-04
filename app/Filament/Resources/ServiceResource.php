<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Услуги';
    protected static ?string $pluralModelLabel = 'Услуги';
    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_bg')
                    ->label('Име (БГ)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('slug')
                    ->label('Слъг (URL)')
                    ->required()
                    ->options([
                        'weddings' => '/weddings — Сватби',
                        'proms' => '/proms — Балове',
                        'baptism' => '/baptism — Кръщенета',
                        'graduation' => '/graduation — Изпращане',
                        'commercial' => '/commercial — Реклама и Бизнес',
                        'events' => '/events — Събитийна фотография',
                    ])
                    ->searchable(),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Описание (БГ)')
                    ->columnSpanFull(),
                Forms\Components\Select::make('icon_class')
                    ->label('Икона')
                    ->searchable()
                    ->options([
                        'fas fa-heart' => '❤️ Сърце',
                        'fas fa-star' => '⭐ Звезда',
                        'fas fa-camera' => '📷 Камера',
                        'fas fa-video' => '🎥 Видеокамера',
                        'fas fa-user-graduate' => '🎓 Абитуриент',
                        'fas fa-briefcase' => '💼 Куфарче',
                        'fas fa-baby' => '👶 Бебе',
                        'fas fa-church' => '⛪ Църква',
                        'fas fa-ring' => '💍 Пръстен',
                        'fas fa-gift' => '🎁 Подарък',
                        'fas fa-music' => '🎵 Музика',
                        'fas fa-glass-cheers' => '🥂 Наздраве',
                        'fas fa-birthday-cake' => '🎂 Торта',
                        'fas fa-users' => '👥 Хора',
                        'fas fa-image' => '🖼️ Снимка',
                        'fas fa-film' => '🎞️ Филм',
                        'fas fa-magic' => '✨ Магия',
                        'fas fa-gem' => '💎 Диамант',
                        'fas fa-crown' => '👑 Корона',
                        'fas fa-dove' => '🕊️ Гълъб',
                        'fas fa-hand-holding-heart' => '🤲 Грижа',
                        'fas fa-palette' => '🎨 Палитра',
                        'fas fa-shopping-bag' => '🛍️ Пазаруване',
                        'fas fa-building' => '🏢 Сграда',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слъг')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PackagesRelationManager::class,
            RelationManagers\ExtrasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
