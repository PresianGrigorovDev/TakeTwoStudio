<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromFaqResource\Pages;
use App\Models\PromFaq;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class PromFaqResource extends Resource
{
    protected static ?string $model = PromFaq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Абитуриенти';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Въпроси и отговори';
    protected static ?string $modelLabel = 'Въпрос';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('question')
                ->label('Въпрос')
                ->required()
                ->columnSpanFull(),

            Textarea::make('answer')
                ->label('Отговор')
                ->required()
                ->rows(4)
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->label('Наредба')
                ->numeric()
                ->default(0),

            Toggle::make('is_visible')
                ->label('Видим')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                TextColumn::make('question')
                    ->label('Въпрос')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('answer')
                    ->label('Отговор')
                    ->limit(60),

                IconColumn::make('is_visible')
                    ->label('Видим')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromFaqs::route('/'),
            'create' => Pages\CreatePromFaq::route('/create'),
            'edit'   => Pages\EditPromFaq::route('/{record}/edit'),
        ];
    }
}
