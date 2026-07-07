<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaptismExtraResource\Pages;
use App\Models\ServiceExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BaptismExtraResource extends Resource
{
    protected static ?string $model = ServiceExtra::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'Кръщения';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Добавки';
    protected static ?string $modelLabel = 'Добавка';
    protected static ?string $slug = 'baptism-extras';

    public static function getEloquentQuery(): Builder
    {
        $service = \App\Models\Service::where('slug', 'baptism')->first();
        return parent::getEloquentQuery()->where('service_id', $service?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('service_id')
                ->default(fn () => \App\Models\Service::where('slug', 'baptism')->first()?->id),
            Forms\Components\TextInput::make('label_bg')
                ->label('Заглавие')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('price_eur')
                ->label('Цена (EUR)')
                ->numeric()
                ->prefix('€')
                ->required(),
            Forms\Components\Select::make('input_type')
                ->label('Тип избор')
                ->options([
                    'checkbox' => 'Квадратче (Checkbox)',
                    'radio' => 'Бутон (Radio)',
                    'number' => 'Число',
                ])
                ->required(),
            Forms\Components\TextInput::make('group_name_bg')
                ->label('Група (напр. Локация)')
                ->maxLength(100),
            Forms\Components\TextInput::make('description_bg')
                ->label('Кратко описание')
                ->maxLength(255),
            Forms\Components\TextInput::make('icon_class')
                ->label('Икона (FontAwesome)')
                ->placeholder('fas fa-star')
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
                Tables\Columns\TextColumn::make('label_bg')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('input_type')
                    ->label('Тип'),
                Tables\Columns\TextColumn::make('group_name_bg')
                    ->label('Група'),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('#')
                    ->sortable(),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order')
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBaptismExtras::route('/'),
            'create' => Pages\CreateBaptismExtra::route('/create'),
            'edit'   => Pages\EditBaptismExtra::route('/{record}/edit'),
        ];
    }
}
