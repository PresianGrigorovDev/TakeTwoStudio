<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Често задавани въпроси';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $pluralModelLabel = 'Въпроси и отговори';
    protected static ?string $modelLabel = 'Въпрос и отговор';

    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('question')
                    ->label('Въпрос')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('answer')
                    ->label('Отговор')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('page_slug')
                    ->label('Категория / Страница')
                    ->options([
                        'general' => 'Общи',
                        'weddings' => 'Сватби',
                        'proms' => 'Абитуриенти',
                        'baptisms' => 'Кръщенета',
                        'commercial' => 'Рекламно заснемане',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label('Въпрос')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('answer')
                    ->label('Отговор')
                    ->limit(50),
                Tables\Columns\TextColumn::make('page_slug')
                    ->label('Категория')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'general' => 'Общи',
                        'weddings' => 'Сватби',
                        'proms' => 'Абитуриенти',
                        'baptisms' => 'Кръщенета',
                        'commercial' => 'Рекламно заснемане',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създаден на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновен на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page_slug')
                    ->label('Категория')
                    ->options([
                        'general' => 'Общи',
                        'weddings' => 'Сватби',
                        'proms' => 'Абитуриенти',
                        'baptisms' => 'Кръщенета',
                        'commercial' => 'Рекламно заснемане',
                    ]),
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
