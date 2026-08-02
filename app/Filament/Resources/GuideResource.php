<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuideResource\Pages;
use App\Models\Guide;
use App\Support\ImageOptimizer;
use App\Support\ImagePath;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Contenido';

    protected static ?string $navigationLabel = 'Guías / Equipo';

    protected static ?string $modelLabel = 'Guía';

    protected static ?string $pluralModelLabel = 'Guías';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(255),

                // Misma convención de FileUpload + ImageOptimizer que las fotos
                // del itinerario de tours (TourResource): disco "public",
                // optimizado a WebP, reemplazo borra el archivo anterior porque
                // es un campo de una sola imagen.
                Forms\Components\FileUpload::make('photo')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory('guides')
                    ->imageEditor()
                    ->maxSize(4096)
                    ->saveUploadedFileUsing(ImageOptimizer::saver('guides', 1000, deletePrevious: true))
                    ->helperText('Se optimiza automáticamente a WebP (máx. 1000px de ancho). Tamaño máximo por archivo: 4 MB.')
                    ->columnSpanFull(),

                Forms\Components\Fieldset::make('Rol / cargo')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('role_es')->label('Rol (Español)')->maxLength(255)->placeholder('Guía de turismo'),
                        Forms\Components\TextInput::make('role_en')->label('Role (English)')->maxLength(255)->placeholder('Tour guide'),
                        Forms\Components\TextInput::make('role_pt')->label('Cargo (Português)')->maxLength(255)->placeholder('Guia turístico'),
                    ]),

                Forms\Components\Fieldset::make('Biografía corta')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Textarea::make('bio_es')->label('Bio (Español)')->rows(3)->columnSpanFull(),
                        Forms\Components\Textarea::make('bio_en')->label('Bio (English)')->rows(3)->columnSpanFull(),
                        Forms\Components\Textarea::make('bio_pt')->label('Bio (Português)')->rows(3)->columnSpanFull(),
                    ]),

                Forms\Components\TagsInput::make('languages')
                    ->label('Idiomas que habla')
                    ->placeholder('Agregar idioma')
                    ->helperText('Ej: Español, Inglés, Quechua.')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('years_experience')
                    ->label('Años de experiencia')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(80),

                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->required()
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activo (visible en el sitio)')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->getStateUsing(fn ($record) => ImagePath::url($record->photo))
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role_es')
                    ->label('Rol')
                    ->limit(30),
                Tables\Columns\TextColumn::make('years_experience')
                    ->label('Años exp.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
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
            'index' => Pages\ListGuides::route('/'),
            'create' => Pages\CreateGuide::route('/create'),
            'edit' => Pages\EditGuide::route('/{record}/edit'),
        ];
    }
}
