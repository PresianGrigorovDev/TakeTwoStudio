<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Услуги';
    protected static ?string $pluralModelLabel = 'Услуги';
    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_bg')
                    ->label('Име (БГ)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('slug')
                    ->label('Слъг (URL)')
                    ->required()
                    ->options([
                        'weddings' => '/weddings — Сватби',
                        'proms' => '/proms — Балове',
                        'baptism' => '/baptism — Кръщенета',
                        'graduation' => '/graduation — Изпращане',
                        'commercial' => '/commercial — Реклама и Бизнес',
                        'family' => '/family — Семейна Фотография',
                        'portrait' => '/portrait — Портретна Фотография',
                        'automotive' => '/automotive — Автомобилна Фотография',
                        'architectural' => '/architectural — Архитектурна Фотография',
                        'events' => '/events — Събитийна Фотография',
                    ])
                    ->searchable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна (видима на сайта)')
                    ->default(true),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Описание (БГ)')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('hero_image')
                    ->label('Hero снимка (фон)')
                    ->image()
                    ->directory('hero-images')
                    ->disk('public')
                    ->imageEditor()
                    ->columnSpanFull()
                    ->maxSize(30720),
                Forms\Components\TextInput::make('video_url')
                    ->label('Линк към видео (YouTube / Vimeo / Instagram / MP4)')
                    ->helperText('Въведете линк към видео, което да се показва в страницата на услугата (от YouTube, Vimeo, Instagram Reels или външен MP4 файл).')
                    ->url()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('video_title')
                    ->label('Заглавие на видеото (за Google / AI търсачки)')
                    ->helperText('Показва се като VideoObject.name в schema.org. Пример: „Сватбен филм 4K – Евксиноград, Варна“.')
                    ->maxLength(150),
                Forms\Components\DatePicker::make('video_uploaded_at')
                    ->label('Дата на публикуване на видеото')
                    ->helperText('Задължителна за VideoObject schema; ако е празна, се ползва датата на последна промяна на услугата.'),
                Forms\Components\FileUpload::make('video_path')
                    ->label('Или качете локално видео (MP4 / WebM)')
                    ->helperText('Ако нямате линк, качете кратък видео клип директно тук (максимум 100MB).')
                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                    ->directory('service-videos')
                    ->disk('public')
                    ->maxSize(102400)
                    ->columnSpanFull(),
                Forms\Components\Select::make('icon_class')
                    ->label('Икона')
                    ->searchable()
                    ->options([
                        'fas fa-heart' => '❤️ Сърце',
                        'fas fa-star' => '⭐ Звезда',
                        'fas fa-camera' => '📷 Камера',
                        'fas fa-video' => '🎥 Видеокамера',
                        'fas fa-user-graduate' => '🎓 Абитуриент',
                        'fas fa-briefcase' => '💼 Куфарче',
                        'fas fa-baby' => '👶 Бебе',
                        'fas fa-church' => '⛪ Църква',
                        'fas fa-ring' => '💍 Пръстен',
                        'fas fa-gift' => '🎁 Подарък',
                        'fas fa-music' => '🎵 Музика',
                        'fas fa-glass-cheers' => '🥂 Наздраве',
                        'fas fa-birthday-cake' => '🎂 Торта',
                        'fas fa-users' => '👥 Хора',
                        'fas fa-image' => '🖼️ Снимка',
                        'fas fa-film' => '🎞️ Филм',
                        'fas fa-magic' => '✨ Магия',
                        'fas fa-gem' => '💎 Диамант',
                        'fas fa-crown' => '👑 Корона',
                        'fas fa-dove' => '🕊️ Гълъб',
                        'fas fa-hand-holding-heart' => '🤲 Грижа',
                        'fas fa-palette' => '🎨 Палитра',
                        'fas fa-shopping-bag' => '🛍️ Пазаруване',
                        'fas fa-building' => '🏢 Сграда',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слъг')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\ImageColumn::make('hero_image')
                    ->label('Hero снимка')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
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
            RelationManagers\PackagesRelationManager::class,
            RelationManagers\ExtrasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
