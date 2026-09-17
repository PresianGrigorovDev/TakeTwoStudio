<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameStickerResource\Pages;
use App\Http\Controllers\GameController;
use App\Models\GameEvent;
use App\Models\GameSticker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

/**
 * The studio's QR stickers for the /igra game. A sticker is a (target, loc) pair;
 * the public link and the QR code (rendered in the browser by public/js/admin/game-qr.js)
 * are derived from it, and the counters come from game_events with the same target + loc.
 */
class GameStickerResource extends Resource
{
    protected static ?string $model = GameSticker::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR стикери';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $modelLabel = 'QR стикер';

    protected static ?string $pluralModelLabel = 'QR стикери';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Стикер')
                    ->description('Линкът и QR кодът се генерират автоматично от играта и локацията. Локацията е частта loc=… в адреса и по нея се броят сканиранията.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Име / място')
                            ->placeholder('напр. Морска градина – главен вход')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                                // Suggest a slug from the name while creating, until the user types their own.
                                if ($operation === 'create' && blank($get('loc'))) {
                                    $set('loc', GameController::sanitizeLoc($state) ?? '');
                                }
                            }),

                        Forms\Components\Select::make('target')
                            ->label('Игра')
                            ->options(GameEventResource::TARGET_LABELS)
                            ->default('prom')
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\TextInput::make('loc')
                            ->label('Локация в адреса (loc)')
                            ->prefix('loc=')
                            ->placeholder('morska-gradina')
                            ->required()
                            ->maxLength(40)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('loc', GameController::sanitizeLoc($state) ?? ''))
                            ->rules(['regex:'.GameEvent::LOC_PATTERN])
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('target', $get('target')))
                            ->validationMessages([
                                'regex' => 'Само малки латински букви, цифри и тире (до 40 знака).',
                                'unique' => 'Вече има стикер с тази локация за същата игра.',
                            ])
                            ->helperText('Малки латински букви, цифри и тире, до 40 знака. Уникална за играта. По-къса локация = по-прост QR код.'),

                        Forms\Components\DatePicker::make('placed_at')
                            ->label('Поставен на')
                            ->native(false)
                            ->displayFormat('d.m.Y'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Бележки')
                            ->placeholder('адрес, кой го е поставил, размер на стикера…')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('url')
                            ->label('Линк')
                            ->content(fn (?GameSticker $record): string => $record?->url ?? '')
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Стикер')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (GameSticker $record): ?string => $record->notes ? Str::limit($record->notes, 60) : null)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('target')
                    ->label('Игра')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'wedding' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state): string => GameEventResource::TARGET_LABELS[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('loc')
                    ->label('loc')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Линк')
                    ->state(fn (GameSticker $record): string => $record->url)
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextColumnSize::ExtraSmall)
                    ->limit(46)
                    ->tooltip(fn (GameSticker $record): string => $record->url)
                    ->copyable()
                    ->copyMessage('Линкът е копиран'),

                Tables\Columns\TextColumn::make('scans')
                    ->label('Сканирания')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wins')
                    ->label('Победи')
                    ->numeric()
                    ->alignRight()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vouchers')
                    ->label('Ваучери')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('placed_at')
                    ->label('Поставен на')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('target')
                    ->label('Игра')
                    ->options(GameEventResource::TARGET_LABELS),
            ])
            ->actions([
                Tables\Actions\Action::make('qr')
                    ->label('QR код')
                    ->icon('heroicon-m-qr-code')
                    ->color('primary')
                    ->modalHeading(fn (GameSticker $record): string => 'QR код: '.$record->name)
                    ->modalDescription(fn (GameSticker $record): string => $record->url)
                    ->modalContent(fn (GameSticker $record) => view('filament.game-stickers.qr', ['sticker' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Затвори')
                    ->modalWidth(MaxWidth::ThreeExtraLarge),

                Tables\Actions\Action::make('open')
                    ->label('Отвори')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (GameSticker $record): string => $record->url, shouldOpenInNewTab: true),

                Tables\Actions\EditAction::make()->label('Редакция'),
                Tables\Actions\DeleteAction::make()->label('Изтрий'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Изтрий избраните'),
            ])
            ->emptyStateHeading('Няма създадени стикери')
            ->emptyStateDescription('Създай стикер с име, игра и локация – линкът и QR кодът се генерират автоматично.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Нов стикер'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withEventCounts();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameStickers::route('/'),
            'create' => Pages\CreateGameSticker::route('/create'),
            'edit' => Pages\EditGameSticker::route('/{record}/edit'),
        ];
    }
}
