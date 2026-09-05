<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Резервации';
    protected static ?string $navigationLabel = 'Резервации';
    protected static ?string $modelLabel = 'Резервация';
    protected static ?string $pluralModelLabel = 'Резервации';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Име на клиента')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('Телефон')
                ->tel()
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('email')
                ->label('Имейл')
                ->email()
                ->maxLength(255),
            Forms\Components\DatePicker::make('event_date')
                ->label('Дата на събитието')
                ->required()
                ->minDate(now()),
            Forms\Components\Select::make('start_time')
                ->label('Начален час')
                ->options(fn () => array_combine(
                    \App\Http\Controllers\BookingController::getWorkingHours(),
                    \App\Http\Controllers\BookingController::getWorkingHours()
                ))
                ->required(),
            Forms\Components\Select::make('end_time')
                ->label('Краен час')
                ->options(function () {
                    $hours = [];
                    for ($h = \App\Http\Controllers\BookingController::getWorkStart() + 1; $h <= \App\Http\Controllers\BookingController::getWorkEnd(); $h++) {
                        $val = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                        $hours[$val] = $val;
                    }
                    return $hours;
                })
                ->required(),
            Forms\Components\Select::make('service_type')
                ->label('Тип услуга')
                ->options([
                    'Сватба' => 'Сватба',
                    'Абитуриентски Бал' => 'Абитуриентски Бал',
                    'Свето Кръщене' => 'Свето Кръщене',
                    'Изпращане' => 'Изпращане',
                    'Реклама и Бизнес' => 'Реклама и Бизнес',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    'pending' => 'Чакаща',
                    'confirmed' => 'Потвърдена',
                    'rejected' => 'Отказана',
                    'cancelled' => 'Отменена',
                ])
                ->required()
                ->default('pending'),
            Forms\Components\Textarea::make('message')
                ->label('Съобщение от клиента')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('admin_notes')
                ->label('Вътрешни бележки')
                ->columnSpanFull(),
            Forms\Components\Select::make('team_member_id')
                ->label('Назначен фотограф')
                ->relationship('teamMember', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('order_id')
                ->label('Свързана поръчка')
                ->relationship('order', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Клиент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Час')
                    ->formatStateUsing(fn ($state, $record) => $record->start_time . ' – ' . $record->end_time)
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Услуга')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teamMember.name')
                    ->label('Фотограф')
                    ->default('-')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Чакаща',
                        'confirmed' => 'Потвърдена',
                        'rejected' => 'Отказана',
                        'cancelled' => 'Отменена',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадена')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Чакаща',
                        'confirmed' => 'Потвърдена',
                        'rejected' => 'Отказана',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Услуга')
                    ->options([
                        'Сватба' => 'Сватба',
                        'Абитуриентски Бал' => 'Абитуриентски Бал',
                        'Свето Кръщене' => 'Свето Кръщене',
                        'Изпращане' => 'Изпращане',
                        'Реклама и Бизнес' => 'Реклама и Бизнес',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Потвърди')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Потвърждаване на резервация')
                    ->modalDescription('Сигурни ли сте, че искате да потвърдите тази резервация? Ще бъде изпратен имейл до клиента.')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);
                        if ($record->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                    ->send(new \App\Mail\BookingConfirmedNotification($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send booking confirmation: " . $e->getMessage());
                            }
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Откажи')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отказване на резервация')
                    ->modalDescription('Сигурни ли сте, че искате да откажете тази резервация? Ще бъде изпратен имейл до клиента.')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'rejected']);
                        if ($record->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                    ->send(new \App\Mail\BookingRejectedNotification($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send booking rejection: " . $e->getMessage());
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
