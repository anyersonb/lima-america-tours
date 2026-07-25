<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use App\Support\ImagePath;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Regiones';

    protected static ?string $modelLabel = 'Región';

    protected static ?string $pluralModelLabel = 'Regiones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_es')
                    ->required()
                    ->maxLength(255)
                    ->label('Nombre (Español)'),
                Forms\Components\TextInput::make('name_en')
                    ->maxLength(255)
                    ->label('Name (English)'),
                Forms\Components\TextInput::make('name_pt')
                    ->maxLength(255)
                    ->label('Nome (Português)'),
                Forms\Components\Textarea::make('description_es')
                    ->label('Descripción (Español)')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description_en')
                    ->label('Description (English)')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description_pt')
                    ->label('Descrição (Português)')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('hero_image')
                    ->image(),
                Forms\Components\TextInput::make('eyebrow_es')
                    ->maxLength(255)
                    ->label('Eyebrow (Español)'),
                Forms\Components\TextInput::make('eyebrow_en')
                    ->maxLength(255)
                    ->label('Eyebrow (English)'),
                Forms\Components\TextInput::make('eyebrow_pt')
                    ->maxLength(255)
                    ->label('Eyebrow (Português)'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('seo_title')
                    ->maxLength(255),
                Forms\Components\TextInput::make('seo_description')
                    ->maxLength(320),
                Forms\Components\FileUpload::make('seo_image')
                    ->image(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_es')
                    ->label('Nombre (ES)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Nombre (EN)')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('hero_image')
                    ->label('Imagen')
                    ->getStateUsing(fn ($record) => ImagePath::url($record->hero_image)),
                Tables\Columns\TextColumn::make('eyebrow_es')
                    ->label('Eyebrow (ES)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('eyebrow_en')
                    ->label('Eyebrow (EN)')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seo_title')
                    ->label('SEO — Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seo_description')
                    ->label('SEO — Descripción')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('seo_image')
                    ->label('SEO — Imagen')
                    ->getStateUsing(fn ($record) => ImagePath::url($record->seo_image)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
