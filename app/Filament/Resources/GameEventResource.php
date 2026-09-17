<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameEventResource\Pages;
use App\Models\GameEvent;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only log of the QR game events. The only write is the row action that
 * marks a voucher code as redeemed after the studio honours it in Instagram DM.
 */
class GameEventResource extends Resource
{
    protected static ?string $model = GameEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR Игра – събития';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $modelLabel = 'Събитие от играта';

    protected static ?string $pluralModelLabel = 'QR Игра – събития';

    protected static ?int $navigationSort = 2;

    public const TARGET_LABELS = [
        'prom' => 'Бал',
        'wedding' => 'Сватба',
    ];

    public const EVENT_LABELS = [
        'scan' => 'Сканиране',
        'win' => 'Победа',
        'lose' => 'Загуба',
        'voucher' => 'Ваучер',
        'share' => 'Споделяне',
        'use' => 'Към сайта',
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** Voucher codes generated in the last 72 hours (their validity window). */
    public static function getNavigationBadge(): ?string
    {
        $count = GameEvent::query()
            ->where('event', 'voucher')
            ->where('created_at', '>=', now()->subHours(72))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Ваучери от последните 72 часа';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Кодовете от играта НЕ работят в калкулаторите – проверявай ги тук: код + дата (72 ч) + Story с таг.')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и час')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->sortable(),

                Tables\Columns\TextColumn::make('target')
                    ->label('Игра')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'wedding' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state): string => self::TARGET_LABELS[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('loc')
                    ->label('Локация')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Събитие')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'win' => 'success',
                        'lose' => 'danger',
                        'voucher' => 'warning',
                        'share' => 'info',
                        'use' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => self::EVENT_LABELS[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount')
                    ->label('Отстъпка')
                    ->state(function (GameEvent $record): ?string {
                        if ($record->event !== 'voucher') {
                            return null;
                        }
                        $percent = (int) ($record->meta['percent'] ?? 0);
                        $shared = (int) ($record->meta['percent_shared'] ?? 0);
                        $boosted = ! empty($record->meta['boosted_at']);

                        return $percent > 0 ? $percent.'%'.($boosted ? ' ✓ споделено' : ($shared > $percent ? ' (→ '.$shared.'% при споделяне)' : '')) : null;
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Кодът е копиран')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('redeemed_at')
                    ->label('Използван на')
                    ->dateTime('d.m.Y H:i', 'Europe/Sofia')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('meta')
                    ->label('Детайли')
                    ->getStateUsing(function (GameEvent $record): ?array {
                        $meta = collect($record->meta ?? [])
                            ->map(fn ($value, string $key): string => $key.': '.(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)))
                            ->values()
                            ->all();

                        return $meta === [] ? null : $meta;
                    })
                    ->listWithLineBreaks()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextColumnSize::ExtraSmall)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('target')
                    ->label('Игра')
                    ->options(self::TARGET_LABELS),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Събитие')
                    ->options(self::EVENT_LABELS),

                Tables\Filters\SelectFilter::make('loc')
                    ->label('Локация')
                    ->options(fn (): array => GameEvent::query()
                        ->whereNotNull('loc')
                        ->distinct()
                        ->orderBy('loc')
                        ->pluck('loc', 'loc')
                        ->all()),

                Tables\Filters\TernaryFilter::make('redeemed_at')
                    ->label('Използван')
                    ->nullable()
                    ->placeholder('Всички')
                    ->trueLabel('Използвани')
                    ->falseLabel('Неизползвани'),
            ])
            ->actions([
                Tables\Actions\Action::make('redeem')
                    ->label('Маркирай като използван')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Маркирай кода като използван')
                    ->modalDescription(fn (GameEvent $record): string => "Кодът {$record->code} ще бъде отбелязан като използван. Преди това провери: ред „Ваучер“ от последните 72 часа и Instagram Story с таг.")
                    ->modalSubmitActionLabel('Маркирай')
                    ->visible(fn (GameEvent $record): bool => $record->event === 'voucher' && $record->redeemed_at === null)
                    ->action(function (GameEvent $record): void {
                        $record->update(['redeemed_at' => now()]);

                        Notification::make()
                            ->title('Кодът е отбелязан като използван')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Read-only resource, no bulk actions permitted
            ])
            ->emptyStateHeading('Все още няма събития от играта.')
            ->emptyStateDescription('Редовете се появяват, когато някой сканира QR стикер и отвори /igra.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameEvents::route('/'),
        ];
    }
}
