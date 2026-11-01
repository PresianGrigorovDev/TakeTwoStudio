<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use App\Support\ContentVariables;
use Illuminate\Support\HtmlString;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationGroup = 'Съдържание';

    protected static ?string $navigationLabel = 'Въпроси (FAQ) – всички';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Въпроси и отговори';

    protected static ?string $modelLabel = 'Въпрос и отговор';

    /** Rejects {{keys}} that do not exist (typo, deleted package). */
    private static function knownVariablesRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if ($unknown = ContentVariables::unknownKeys($value)) {
                $fail('Непозната променлива: {{'.implode('}}, {{', $unknown).'}}. Изберете от списъка след „{{“.');
            }
        };
    }

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
                    ->extraInputAttributes(fn () => ['data-tt-variables' => json_encode(ContentVariables::forEditor(), JSON_UNESCAPED_UNICODE)])
                    ->rule(fn () => self::knownVariablesRule())
                    ->live(onBlur: true)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('answer')
                    ->label('Отговор')
                    ->required()
                    ->rows(5)
                    ->helperText(new HtmlString('Идеалният отговор е до 60 думи, започва с директния отговор и споменава Варна / услугата / цена, където има смисъл. <strong>Цени, срокове и контакти не се пишат на ръка:</strong> напишете <code>{{</code> и изберете от списъка (напр. <code>{{prom.price.min}}</code>), стойността идва от базата и се обновява сама.'))
                    ->extraInputAttributes(fn () => ['data-tt-variables' => json_encode(ContentVariables::forEditor(), JSON_UNESCAPED_UNICODE)])
                    ->rule(fn () => self::knownVariablesRule())
                    ->live(onBlur: true)
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('preview')
                    ->label('Как ще изглежда на сайта')
                    ->content(fn (Forms\Get $get) => new HtmlString('<strong>'.e(ContentVariables::render($get('question'))).'</strong><br>'.nl2br(e(ContentVariables::render($get('answer'))))))
                    ->visible(fn (Forms\Get $get) => str_contains((string) $get('question').(string) $get('answer'), '{{'))
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
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Faq::PAGES[$state] ?? $state) : '—')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->label('Въпрос')
                    ->searchable()
                    ->wrap()
                    ->limit(90),
                Tables\Columns\TextColumn::make('answer')
                    ->label('Отговор')
                    ->searchable()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Видим'),
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
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Видимост'),
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
