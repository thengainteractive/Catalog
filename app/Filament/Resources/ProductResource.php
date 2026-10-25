<?php

namespace Modules\Catalog\Filament\Resources;

use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ImageProcessingService;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static \UnitEnum|string|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                SchemaComponents\Group::make([
                    SchemaComponents\Section::make('Product Details')
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
                                ->unique(Product::class, 'slug', ignoreRecord: true)
                                ->dehydrated(),
                            Components\Select::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull()
                                ->createOptionForm([
                                    Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug($state)))
                                        ->extraInputAttributes([
                                            'x-on:blur' => "\$wire.set('mountedActions.0.data.slug', \$el.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''))",
                                        ]),
                                    Components\TextInput::make('slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(\Modules\Catalog\Models\Category::class, 'slug')
                                        ->dehydrated(),
                                    Components\FileUpload::make('cover_image')
                                        ->label('Cover Image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('catalog/categories')
                                        ->saveUploadedFileUsing(function (Components\BaseFileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                                            return \Modules\Catalog\Services\ImageProcessingService::processAndStore(
                                                file: $file,
                                                directory: $component->getDirectory(),
                                                disk: $component->getDiskName(),
                                                maxWidth: 1024,
                                                quality: 60
                                            );
                                        })
                                        ->columnSpanFull(),
                                    Components\Textarea::make('description')
                                        ->rows(3),
                                    Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ])
                                ->editOptionForm([
                                    Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug($state)))
                                        ->extraInputAttributes([
                                            'x-on:blur' => "\$wire.set('mountedActions.0.data.slug', \$el.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''))",
                                        ]),
                                    Components\TextInput::make('slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(\Modules\Catalog\Models\Category::class, 'slug', ignoreRecord: true)
                                        ->dehydrated(),
                                    Components\FileUpload::make('cover_image')
                                        ->label('Cover Image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('catalog/categories')
                                        ->saveUploadedFileUsing(function (Components\BaseFileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                                            return \Modules\Catalog\Services\ImageProcessingService::processAndStore(
                                                file: $file,
                                                directory: $component->getDirectory(),
                                                disk: $component->getDiskName(),
                                                maxWidth: 1024,
                                                quality: 60
                                            );
                                        })
                                        ->columnSpanFull(),
                                    Components\Textarea::make('description')
                                        ->rows(3),
                                    Components\Toggle::make('is_active')
                                        ->label('Active'),
                                ]),
                            Components\RichEditor::make('description')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),

                SchemaComponents\Group::make([
                    SchemaComponents\Section::make('Pricing')
                        ->columns(2)
                        ->schema([
                            Components\TextInput::make('actual_price')
                                ->label('Actual Price')
                                ->numeric()
                                ->prefix('₹')
                                ->required(),
                            Components\TextInput::make('sale_price')
                                ->label('Sale Price')
                                ->numeric()
                                ->prefix('₹')
                                ->nullable(),
                        ]),

                    SchemaComponents\Section::make('Gallery')
                        ->schema([
                            Components\FileUpload::make('gallery_images')
                                ->label('Gallery Images')
                                ->multiple()
                                ->reorderable()
                                ->image()
                                ->disk('public')
                                ->directory('catalog/products')
                                ->panelLayout('grid')
                                ->imageEditor()
                                ->saveUploadedFileUsing(function (Components\BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                    return ImageProcessingService::processAndStore(
                                        file: $file,
                                        directory: $component->getDirectory(),
                                        disk: $component->getDiskName(),
                                        maxWidth: 1024,
                                        quality: 60
                                    );
                                }),
                        ]),
                ])->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gallery_images')
                    ->label('Image')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('actual_price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->money('INR')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\Modules\Catalog\Filament\Exports\ProductExporter::class)
                        ->label('Export Selected'),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
