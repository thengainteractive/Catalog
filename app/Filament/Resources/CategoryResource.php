<?php

namespace Modules\Catalog\Filament\Resources;

use Modules\Catalog\Filament\Resources\CategoryResource\Pages;
use Modules\Catalog\Models\Category;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaComponents\Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set, ?string $old) {
                                if ($operation === 'create' || blank($old)) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->extraInputAttributes([
                                'x-on:blur' => "\$wire.set('data.slug', \$el.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''))",
                            ]),
                        Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'slug', ignoreRecord: true)
                            ->dehydrated(),
                        Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ]),

                SchemaComponents\Section::make('Media')
                    ->schema([
                        Components\FileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->image()
                            ->disk('public')
                            ->directory('catalog/categories')
                            ->imageEditor()
                            ->saveUploadedFileUsing(function (Components\BaseFileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                                return \Modules\Catalog\Services\ImageProcessingService::processAndStore(
                                    file: $file,
                                    directory: $component->getDirectory(),
                                    disk: $component->getDiskName(),
                                    maxWidth: 1024,
                                    quality: 60
                                );
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->circular()
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CategoryResource\RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
