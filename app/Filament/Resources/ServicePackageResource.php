<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicePackageResource\Pages;
use App\Filament\Resources\ServicePackageResource\RelationManagers;
use App\Models\ServicePackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicePackageResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $navigationGroup = 'Услуги';

    protected static ?string $model = ServicePackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name_bg')
                    ->required(),
                Forms\Components\TextInput::make('name_bg')
                    ->label('Name (BG)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price_eur')
                    ->label('Price (EUR)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Description (BG)')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_default')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name_bg')
                    ->label('Service')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name_bg'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicePackages::route('/'),
            'create' => Pages\CreateServicePackage::route('/create'),
            'edit' => Pages\EditServicePackage::route('/{record}/edit'),
        ];
    }
}
