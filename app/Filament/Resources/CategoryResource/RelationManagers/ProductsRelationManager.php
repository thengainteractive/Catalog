<?php

namespace Modules\Catalog\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set, ?string $old) {
                        if ($operation === 'create' || blank($old)) {
                            $set('slug', \Illuminate\Support\Str::slug($state));
                        }
                    }),
                Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(\Modules\Catalog\Models\Product::class, 'slug', ignoreRecord: true)
                    ->dehydrated(),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('gallery_images')
                    ->label('Image')
                    ->circular()
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->money('INR')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
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
}
