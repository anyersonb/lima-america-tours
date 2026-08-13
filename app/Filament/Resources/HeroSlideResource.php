<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroSlideResource\Pages;
use App\Models\HeroSlide;
use App\Support\ImageOptimizer;
use App\Support\ImagePath;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CMS del slider del hero de la home (mockup `01-home.jpeg`). Capa de datos
 * y panel únicamente — el maquetado del slider (markup/JS/CSS) es aparte,
 * ver App\Services\HeroSlidesResolver para el contrato `$heroSlides`.
 */
class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Contenido';

    protected static ?string $navigationLabel = 'Diapositivas del Hero';

    protected static ?string $modelLabel = 'Diapositiva del hero';

    protected static ?string $pluralModelLabel = 'Diapositivas del hero';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Mismo disco/directorio que home_hero_image (Settings →
                // Imágenes del Home) y mismo ancho máximo (1920px): estas
                // diapositivas son el LCP del sitio cuando son la primera.
                Forms\Components\FileUpload::make('image')
                    ->label('Imagen')
                    ->image()
                    ->required()
                    ->disk('media')
                    ->directory('home')
                    ->imageEditor()
                    ->maxSize(6144)
                    ->saveUploadedFileUsing(ImageOptimizer::saver('home', 1920, disk: 'media', deletePrevious: true))
                    ->helperText('Se optimiza automáticamente a WebP (máx. 1920px de ancho). Tamaño máximo por archivo: 6 MB.')
                    ->columnSpanFull(),

                Forms\Components\Fieldset::make('Texto alternativo (accesibilidad y SEO)')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('alt_es')
                            ->label('Alt (Español)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alt_en')
                            ->label('Alt (English)')
                            ->maxLength(255)
                            ->helperText('Vacío = usa el texto en español.'),
                        Forms\Components\TextInput::make('alt_pt')
                            ->label('Alt (Português)')
                            ->maxLength(255)
                            ->helperText('Vazio = usa o texto em espanhol.'),
                    ]),

                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Define el orden del slider, no el orden de creación.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activa (visible en el sitio)')
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->getStateUsing(fn ($record) => ImagePath::homeImage($record->image)),
                Tables\Columns\TextColumn::make('alt_es')
                    ->label('Alt (ES)')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activa'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
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
            'index' => Pages\ListHeroSlides::route('/'),
            'create' => Pages\CreateHeroSlide::route('/create'),
            'edit' => Pages\EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
