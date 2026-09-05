<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationGroup = 'Съдържание';

    protected static ?string $navigationLabel = 'Често задавани въпроси';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 5;

    protected static ?string $pluralModelLabel = 'Въпроси и отговори';

    protected static ?string $modelLabel = 'Въпрос и отговор';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('page_slug')
                    ->label('Страница')
                    ->options(Faq::PAGES)
                    ->required()
                    ->helperText('Въпросът се показва в секцията „Често задавани въпроси“ на тази страница и влиза в FAQPage schema за Google / AI търсачки.'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('question')
                    ->label('Въпрос')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('answer')
                    ->label('Отговор')
                    ->required()
                    ->rows(5)
                    ->helperText('Идеалният отговор е до 60 думи, започва с директния отговор и споменава Варна / услугата / цена, където има смисъл.')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_visible')
                    ->label('Видим')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('page_slug')
                    ->label('Страница')
                    ->formatStateUsing(fn (string $state): string => Faq::PAGES[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->label('Въпрос')
                    ->searchable()
                    ->limit(70),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Видим')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновен на')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page_slug')
                    ->label('Страница')
                    ->options(Faq::PAGES),
            ])
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
