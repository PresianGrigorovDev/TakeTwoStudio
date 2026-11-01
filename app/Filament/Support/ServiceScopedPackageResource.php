<?php

namespace App\Filament\Support;

use App\Models\Service;
use App\Models\ServicePackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Пакети и цени" in a service's own admin group (Сватби, Кръщения) edits the SAME
 * rows the site reads: service_packages of that service, also editable under
 * Услуги → Пакети. (Until 2026-11-02 these sections wrote to wedding_packages /
 * baptism_packages, which no page reads, so price changes never reached the site.)
 */
abstract class ServiceScopedPackageResource extends Resource
{
    protected static ?string $model = ServicePackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $pluralModelLabel = 'Пакети и цени';

    protected static ?string $modelLabel = 'Пакет';

    /** services.slug whose packages this section edits. */
    protected static string $serviceSlug;

    public static function serviceId(): ?int
    {
        return Service::where('slug', static::$serviceSlug)->value('id');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('service_id', static::serviceId() ?? 0);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('service_id')
                ->default(fn () => static::serviceId()),
            Forms\Components\TextInput::make('name_bg')
                ->label('Име на пакета')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('price_eur')
                ->label('Цена (EUR)')
                ->numeric()
                ->prefix('€')
                ->required(),
            Forms\Components\Textarea::make('description_bg')
                ->label('Описание (БГ)')
                ->placeholder('За нов ред използвайте Enter')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('icon_class')
                ->label('Икона (FontAwesome)')
                ->placeholder('fa-star')
                ->maxLength(50),
            Forms\Components\Toggle::make('is_default')
                ->label('По подразбиране?'),
            Forms\Components\TextInput::make('display_order')
                ->label('Поредност')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('По подразбиране')
                    ->boolean(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Поредност')
                    ->sortable(),
            ])
            ->defaultSort('display_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
