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
                            Components\Toggle::make('is_active')
                                ->label('Visible on Storefront')
                                ->helperText('Control whether this product is shown or hidden on the storefront.')
                                ->default(true)
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

                SchemaComponents\Section::make('Product Variants')
                    ->columnSpanFull()
                    ->schema([
                        Components\Toggle::make('has_variants')
                            ->label('This product has multiple variants')
                            ->default(false)
                            ->live(),

                        SchemaComponents\Group::make([
                            Components\Repeater::make('variant_attributes')
                                ->label('Attributes')
                                ->addActionLabel('Add Attribute')
                                ->columns(2)
                                ->formatStateUsing(function ($state, ?Product $record) {
                                    if (! blank($state)) {
                                        return $state;
                                    }
                                    return $record?->variant_attributes ?? [];
                                })
                                ->mutateDehydratedStateUsing(function ($state) {
                                    if (! is_array($state)) {
                                        return [];
                                    }
                                    return array_values($state);
                                })
                                ->schema([
                                    Components\TextInput::make('name')
                                        ->label('Attribute Name')
                                        ->placeholder('e.g. Color, Size')
                                        ->required(),
                                    Components\TagsInput::make('values')
                                        ->label('Values')
                                        ->placeholder('Type value & press Enter')
                                        ->required(),
                                ])
                                ->dehydrated()
                                ->collapsible()
                                ->defaultItems(0)
                                ->live(),

                            SchemaComponents\Actions::make([
                                Actions\Action::make('generate_variants')
                                    ->label('Generate / Sync Variant Combinations')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->color('primary')
                                    ->action(function (callable $get, callable $set) {
                                        $attributes = (array) ($get('variant_attributes') ?? []);
                                        $baseActualPrice = (float) ($get('actual_price') ?? 0);
                                        $baseSalePrice = $get('sale_price') ? (float) $get('sale_price') : null;
                                        $existingVariants = (array) ($get('variants') ?? []);

                                        $cleanAttributes = [];
                                        foreach ($attributes as $attr) {
                                            $name = trim((string) ($attr['name'] ?? ''));
                                            $rawValues = $attr['values'] ?? [];
                                            if (is_string($rawValues)) {
                                                $rawValues = explode(',', $rawValues);
                                            }
                                            $values = [];
                                            foreach ((array) $rawValues as $v) {
                                                $trimmed = trim((string) $v);
                                                if ($trimmed !== '') {
                                                    $values[] = $trimmed;
                                                }
                                            }
                                            if ($name !== '' && ! empty($values)) {
                                                $cleanAttributes[$name] = array_values(array_unique($values));
                                            }
                                        }

                                        if (empty($cleanAttributes)) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Please add at least one attribute with values first.')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        // Compute Cartesian Product
                                        $combinations = [[]];
                                        foreach ($cleanAttributes as $attrName => $attrValues) {
                                            $temp = [];
                                            foreach ($combinations as $comb) {
                                                foreach ($attrValues as $val) {
                                                    $temp[] = array_merge($comb, [$attrName => $val]);
                                                }
                                            }
                                            $combinations = $temp;
                                        }

                                        // Index existing variants to preserve already entered prices and images
                                        $indexedExisting = [];
                                        foreach ($existingVariants as $v) {
                                            $key = $v['title'] ?? '';
                                            if ($key) {
                                                $indexedExisting[$key] = $v;
                                            }
                                        }

                                        $generated = [];
                                        $position = 0;
                                        foreach ($combinations as $comb) {
                                            $title = implode(' / ', array_values($comb));
                                            $existing = $indexedExisting[$title] ?? null;

                                            $generated[] = [
                                                'title' => $title,
                                                'attributes' => $comb,
                                                'sku' => $existing['sku'] ?? null,
                                                'actual_price' => $existing['actual_price'] ?? $baseActualPrice,
                                                'sale_price' => $existing['sale_price'] ?? $baseSalePrice,
                                                'image' => $existing['image'] ?? null,
                                                'is_active' => $existing['is_active'] ?? true,
                                                'position' => $position++,
                                            ];
                                        }

                                        $set('variants', $generated);

                                        \Filament\Notifications\Notification::make()
                                            ->title(count($generated) . ' variant combination(s) generated.')
                                            ->success()
                                            ->send();
                                    }),
                            ]),

                            Components\Repeater::make('variants')
                                ->relationship('variants')
                                ->label('Variants')
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Variant')
                                ->columns(5)
                                ->schema([
                                    Components\TextInput::make('title')
                                        ->label('Combination')
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->columnSpan(1),

                                    Components\TextInput::make('actual_price')
                                        ->label('Price (₹)')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->required()
                                        ->columnSpan(1),

                                    Components\TextInput::make('sale_price')
                                        ->label('Sale Price')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->nullable()
                                        ->columnSpan(1),

                                    Components\FileUpload::make('image')
                                        ->label('Variant Image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('catalog/variants')
                                        ->saveUploadedFileUsing(function (Components\BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                            return ImageProcessingService::processAndStore(
                                                file: $file,
                                                directory: $component->getDirectory(),
                                                disk: $component->getDiskName(),
                                                maxWidth: 1024,
                                                quality: 60
                                            );
                                        })
                                        ->columnSpan(1),

                                    Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpan(1),

                                    Components\Hidden::make('attributes'),
                                    Components\Hidden::make('position'),
                                ])
                                ->collapsible()
                                ->defaultItems(0)
                                ->reorderable('position'),
                        ])
                        ->visible(fn (callable $get) => (bool) $get('has_variants')),
                    ]),
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
                    ->sortable()
                    ->description(fn (Product $record): ?string => $record->has_variants ? ($record->variants()->count() . ' variants') : null),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('actual_price')
                    ->label('Price')
                    ->state(function (Product $record) {
                        if ($record->has_variants) {
                            $min = $record->min_price;
                            $max = $record->max_price;
                            if ($min < $max) {
                                return '₹' . number_format($min, 2) . ' – ₹' . number_format($max, 2);
                            }
                            return '₹' . number_format($min, 2);
                        }
                        return '₹' . number_format((float) ($record->sale_price ?? $record->actual_price), 2);
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_variants')
                    ->label('Variants')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Show/Hide')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Storefront Visibility')
                    ->trueLabel('Visible (Show)')
                    ->falseLabel('Hidden (Hide)')
                    ->placeholder('All Products'),
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
