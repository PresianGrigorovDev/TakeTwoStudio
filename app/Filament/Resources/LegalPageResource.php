<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalPageResource\Pages;
use App\Models\LegalPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;

    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Правни страници';
    protected static ?string $pluralModelLabel = 'Правни страници';
    protected static ?string $modelLabel = 'Правна страница';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('slug')
                    ->label('Тип страница')
                    ->options([
                        'privacy' => 'Политика за поверителност',
                        'terms' => 'Общи условия',
                        'cookies' => 'Политика за бисквитки',
                    ])
                    ->required()
                    ->disabledOn('edit')
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('title_bg')
                    ->label('Заглавие')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('Дата на последна промяна')
                    ->default(now()),
                Forms\Components\Toggle::make('is_published')
                    ->label('Публикувано')
                    ->default(true),
                Forms\Components\RichEditor::make('content_bg')
                    ->label('Съдържание')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'h2', 'h3', 'bulletList', 'orderedList',
                        'link', 'blockquote', 'undo', 'redo',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title_bg')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Публикувано')
                    ->boolean(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Дата')
                    ->date('d.m.Y'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalPages::route('/'),
            'create' => Pages\CreateLegalPage::route('/create'),
            'edit' => Pages\EditLegalPage::route('/{record}/edit'),
        ];
    }
}
