<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $navigationGroup = 'Блог';
    protected static ?string $navigationLabel = 'Публикации';
    protected static ?string $pluralModelLabel = 'Публикации';
    protected static ?string $modelLabel = 'Публикация';

    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Blog Post')
                    ->columnSpanFull()
                    ->tabs([

                        Tabs\Tab::make('Съдържание')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Заглавие')
                                    ->required()
                                    ->maxLength(200)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Слъг (URL)')
                                    ->required()
                                    ->maxLength(220)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Автоматично се генерира от заглавието.')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('author_team_member_id')
                                    ->label('Автор (член на екипа)')
                                    ->helperText('Показва се като автор (Person) в schema.org - важно за E-E-A-T.')
                                    ->relationship('author', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Forms\Components\Select::make('category_id')
                                    ->label('Категория')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Име')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Слъг')
                                            ->required()
                                            ->maxLength(120),
                                    ]),
                                Forms\Components\FileUpload::make('cover_image')
                                    ->label('Главна снимка')
                                    ->required()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('blog')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('excerpt')
                                    ->label('Кратко описание')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('Показва се на listing страницата и като fallback за meta description.')
                                    ->columnSpanFull(),
                                Forms\Components\RichEditor::make('body')
                                    ->label('Съдържание')
                                    ->required()
                                    ->fileAttachmentsDirectory('blog/attachments')
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'blockquote',
                                        'bold',
                                        'bulletList',
                                        'codeBlock',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'link',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Meta Title')
                                    ->maxLength(255)
                                    ->helperText('Ако е празно, ще се ползва заглавието на поста.')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->rows(3)
                                    ->maxLength(300)
                                    ->helperText('Ако е празно, ще се ползва краткото описание. Препоръчително: 150–160 символа.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('meta_keywords')
                                    ->label('Meta Keywords')
                                    ->maxLength(255)
                                    ->helperText('Оставете празно за автоматично генериране от заглавието, категорията и описанието. Ръчно въведени ключови думи не се пипат.')
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('og_image')
                                    ->label('Social Share снимка (OG Image)')
                                    ->image()
                                    ->directory('blog/og')
                                    ->helperText('Ако е празно, ще се ползва главната снимка.')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Настройки')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Публикуван')
                                    ->helperText('Ако е изключен, постът е Draft и не се вижда на сайта.')
                                    ->default(false),
                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Дата на публикуване')
                                    ->seconds(false)
                                    ->helperText('Ако е в бъдещето, постът е насрочен и ще се появи автоматично на тази дата.')
                                    ->default(now()),
                                Forms\Components\TextInput::make('views_count')
                                    ->label('Показвания')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Снимка'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'warning',
                        'draft' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Публикуван',
                        'scheduled' => 'Насрочен',
                        'draft' => 'Чернова',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Дата на публикуване')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Показвания')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'published' => 'Публикувани (активни)',
                        'scheduled' => 'Насрочени',
                        'draft' => 'Чернови',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'published' => $query->published(),
                            'scheduled' => $query->scheduled(),
                            'draft' => $query->draft(),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Публикувай')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Направи чернови')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
