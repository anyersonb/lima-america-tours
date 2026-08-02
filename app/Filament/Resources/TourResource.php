<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Models\Tour;
use App\Support\ImageOptimizer;
use App\Support\ImagePath;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Tours';

    protected static ?string $modelLabel = 'Tour';

    protected static ?string $pluralModelLabel = 'Tours';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tour')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('region_id')
                                        ->relationship('region', 'name_es')
                                        ->searchable()->preload()
                                        ->label('Región'),
                                    Forms\Components\Select::make('category_id')
                                        ->relationship('category', 'name_es')
                                        ->searchable()->preload()
                                        ->label('Categoría'),
                                ]),
                                Forms\Components\TextInput::make('slug')
                                    ->label('URL del tour (slug)')
                                    ->helperText('Es la dirección pública del tour. Se genera automáticamente del título al crear y NO se puede editar después: cambiarla rompería los enlaces ya compartidos e indexados.')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                    ->maxLength(255),
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('duration')->label('Duración')->placeholder('Full Day'),
                                    Forms\Components\TextInput::make('language')->label('Idiomas')->default('Español / Inglés'),
                                    Forms\Components\TextInput::make('group_type')->label('Tipo de grupo')->default('Grupal'),
                                ]),
                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('departure_time')->label('Hora salida')->placeholder('05:00 AM'),
                                    Forms\Components\TextInput::make('return_time')->label('Hora retorno')->placeholder('10:30 PM'),
                                    Forms\Components\TextInput::make('max_capacity')->numeric()->label('Capacidad máxima'),
                                    Forms\Components\TextInput::make('booking_advance_hours')
                                        ->numeric()
                                        ->minValue(0)
                                        ->nullable()
                                        ->suffix('horas')
                                        ->label('Anticipación para reservar (horas)')
                                        ->helperText('Horas mínimas de anticipación con las que el cliente debe reservar. Déjalo vacío si no aplica.'),
                                ]),
                                Forms\Components\Section::make('Precios y oferta')
                                    ->description('Configura el precio y, opcionalmente, activa una oferta especial.')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            static::priceField('price')
                                                ->required()
                                                ->label('Precio actual (AHORA)')
                                                ->helperText('Es el precio que paga el cliente. Debe ser mayor que 0. Puedes escribir los decimales con punto o coma (ej. 120.50 o 120,50).'),
                                            static::priceField('price_before')
                                                ->label('Precio antes (oferta)')
                                                ->helperText('Escribe aquí el precio original (más alto que el actual) para activar la OFERTA ESPECIAL con su % de descuento automático. Déjalo VACÍO si el tour NO tiene oferta.'),
                                            // CRO #2: era un TextInput libre — un editor podía escribir
                                            // cualquier cosa y el panel lo aceptaba sin avisar. Un Select
                                            // con opciones fijas hace el error imposible por tipeo.
                                            // El valor por defecto es la moneda del sitio (Configuración
                                            // → Pagos, hoy USD): un tour etiquetado en OTRA moneda no se
                                            // puede cobrar en línea, el checkout lo aborta a propósito.
                                            Forms\Components\Select::make('currency')
                                                ->required()
                                                ->options([
                                                    'PEN' => 'PEN (Soles)',
                                                    'USD' => 'USD (Dólares)',
                                                ])
                                                // Filament's Select doesn't reject an out-of-list value
                                                // server-side on its own — the `in:` rule is what actually
                                                // blocks it (the UI dropdown only restricts real browser use).
                                                ->rules(['in:PEN,USD'])
                                                ->default(\App\Support\Money::site())
                                                ->native(false)
                                                ->label('Moneda'),
                                        ]),
                                        Forms\Components\Placeholder::make('discount_preview')
                                            ->label('Vista previa del descuento')
                                            ->content(function (Forms\Get $get): string {
                                                // Normalizado (no un simple (float) cast) para que la vista previa
                                                // razone sobre el mismo valor "120,50" → 120.50 que terminará
                                                // guardándose (docs/qa/F7-personas.md §g #2).
                                                $price = Tour::normalizePriceInput($get('price')) ?? 0.0;
                                                $priceBefore = Tour::normalizePriceInput($get('price_before')) ?? 0.0;

                                                // Cada rama tiene su propio copy porque cada una es una causa
                                                // distinta de "sin oferta" (docs/qa/ficha-tour.md hallazgo #8:
                                                // price=0 mostraba el mismo texto que "precio antes vacío",
                                                // un copy incorrecto que confundía la causa real).
                                                if ($price <= 0) {
                                                    return '⚠ Sin oferta: el precio actual (AHORA) debe ser mayor que 0.';
                                                }

                                                if ($priceBefore <= 0) {
                                                    return '— Sin oferta activa (precio antes vacío).';
                                                }

                                                if ($priceBefore <= $price) {
                                                    return '⚠ Sin oferta: el precio antes debe ser MAYOR que el precio actual.';
                                                }

                                                // Misma fórmula que usan la tabla y (a futuro) el front:
                                                // Tour::offerDiscountPercent() es la única fuente de verdad.
                                                $discount = Tour::offerDiscountPercent($price, $priceBefore);
                                                $currency = (string) ($get('currency') ?: \App\Support\Money::site());
                                                $before = Money::format($priceBefore, $currency, 2);
                                                $now = Money::format($price, $currency, 2);

                                                return "✔ OFERTA ESPECIAL -{$discount}% activa — el card mostrará \"ANTES {$before}\" tachado y \"AHORA {$now}\".";
                                            }),
                                    ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('badge_text')->label('Texto del badge')->placeholder('CUPOS LIMITADOS'),
                                    Forms\Components\Select::make('badge_type')->label('Tipo de badge')->options([
                                        'warn' => 'Naranja (advertencia)',
                                        'error' => 'Rojo (urgencia)',
                                        'success' => 'Verde (éxito)',
                                    ]),
                                ]),
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('rating')->numeric()->step(0.1)->minValue(1)->maxValue(5)->default(4.8)->label('Calificación')->helperText('Entre 1 y 5.'),
                                    Forms\Components\TextInput::make('reviews_count')->numeric()->default(0)->label('# reseñas'),
                                    Forms\Components\TextInput::make('order')->numeric()->default(0)->label('Orden'),
                                    Forms\Components\TextInput::make('featured_order')
                                        ->numeric()
                                        ->minValue(1)
                                        ->nullable()
                                        ->label('Orden en "Más Comprados"')
                                        ->helperText('Posición manual en la sección "Tours más Comprados" del home (1 = primero). Vacío = se ordena solo por número de reservas. Aplica a tours con "Destacado" activo.'),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    // docs/qa/F7-personas.md §labels #7 / §g #11: Tours venía en `true` por
                                    // defecto y Blog en `false`, un criterio inconsistente entre recursos del
                                    // mismo panel. Se unifica al criterio "seguro" (apagado = borrador): evita
                                    // que un Tour a medio llenar quede visible en la web sin que el usuario lo
                                    // note, igual que ya pasaba con Blog.
                                    Forms\Components\Toggle::make('is_published')
                                        ->label('Publicado')
                                        ->default(false)
                                        ->helperText('Actívalo para que se vea en la web.'),
                                    Forms\Components\Toggle::make('is_featured')->label('Destacado'),
                                    Forms\Components\Toggle::make('show_best_seller')
                                        ->label('Badge "BEST SELLER"')
                                        ->helperText('Muestra u oculta el escudo BEST SELLER en las tarjetas y el detalle del tour.')
                                        ->default(true),
                                    Forms\Components\Toggle::make('show_offer_badge')
                                        ->label('Badge "Oferta especial"')
                                        ->helperText('Muestra u oculta la pastilla OFERTA ESPECIAL −N% (solo aplica si hay precio antes).')
                                        ->default(true),
                                ]),
                            ]),

                        Tabs\Tab::make('Español')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_es')->required()->maxLength(255)->label('Título'),
                                Forms\Components\TextInput::make('subtitle_es')->maxLength(255)->label('Subtítulo'),
                                Forms\Components\Textarea::make('description_es')->rows(6)->label('Descripción'),
                                Forms\Components\Repeater::make('itinerary_es')->label('Itinerario')->schema([
                                    Forms\Components\TextInput::make('time')->label('Hora'),
                                    Forms\Components\TextInput::make('title')->label('Título'),
                                    Forms\Components\Textarea::make('description')->label('Descripción')->rows(2),
                                    Forms\Components\FileUpload::make('image')
                                        ->label('Imagen referencial')
                                        ->image()
                                        ->disk('public')
                                        ->directory('itinerary')
                                        ->imageEditor()
                                        ->maxSize(4096)
                                        ->saveUploadedFileUsing(ImageOptimizer::saver('itinerary', 1600))
                                        ->helperText('Opcional. Se optimiza automáticamente a WebP. Si se deja vacía, la parada se muestra sin foto.')
                                        ->columnSpanFull(),
                                ])->columns(3)->collapsible()->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                Forms\Components\TagsInput::make('includes_es')->label('Incluye')->placeholder('Agregar item'),
                                Forms\Components\TagsInput::make('excludes_es')->label('No incluye')->placeholder('Agregar item'),
                                Forms\Components\Textarea::make('recommendations_es')->rows(3)->label('Recomendaciones'),
                                Forms\Components\Textarea::make('notes_es')->rows(3)->label('Notas importantes'),
                                Forms\Components\Repeater::make('faqs_es')->label('Preguntas frecuentes')->schema([
                                    Forms\Components\TextInput::make('question')->label('Pregunta')->required(),
                                    Forms\Components\Textarea::make('answer')->label('Respuesta')->rows(3)->required(),
                                ])->collapsible()->reorderable()->defaultItems(0)
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                    ->helperText('Se muestran como acordeón en la página del tour. Déjalo vacío si el tour no lleva FAQs.'),
                            ]),

                        Tabs\Tab::make('English')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_en')->maxLength(255)->label('Title'),
                                Forms\Components\TextInput::make('subtitle_en')->maxLength(255)->label('Subtitle'),
                                Forms\Components\Textarea::make('description_en')->rows(6)->label('Description'),
                                Forms\Components\Repeater::make('itinerary_en')->label('Itinerary')->schema([
                                    Forms\Components\TextInput::make('time')->label('Time'),
                                    Forms\Components\TextInput::make('title')->label('Title'),
                                    Forms\Components\Textarea::make('description')->label('Description')->rows(2),
                                    Forms\Components\FileUpload::make('image')
                                        ->label('Reference image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('itinerary')
                                        ->imageEditor()
                                        ->maxSize(4096)
                                        ->saveUploadedFileUsing(ImageOptimizer::saver('itinerary', 1600))
                                        ->helperText('Optional. Automatically optimized to WebP. If left empty, the stop is shown without a photo.')
                                        ->columnSpanFull(),
                                ])->columns(3)->collapsible()->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                Forms\Components\TagsInput::make('includes_en')->label('Includes'),
                                Forms\Components\TagsInput::make('excludes_en')->label('Excludes'),
                                Forms\Components\Textarea::make('recommendations_en')->rows(3)->label('Recommendations'),
                                Forms\Components\Textarea::make('notes_en')->rows(3)->label('Important notes'),
                                Forms\Components\Repeater::make('faqs_en')->label('Frequently asked questions')->schema([
                                    Forms\Components\TextInput::make('question')->label('Question')->required(),
                                    Forms\Components\Textarea::make('answer')->label('Answer')->rows(3)->required(),
                                ])->collapsible()->reorderable()->defaultItems(0)
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                    ->helperText('Shown as an accordion on the tour page. Falls back to Spanish if empty.'),
                            ]),

                        Tabs\Tab::make('Português')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_pt')->maxLength(255)->label('Título'),
                                Forms\Components\TextInput::make('subtitle_pt')->maxLength(255)->label('Subtítulo'),
                                Forms\Components\Textarea::make('description_pt')->rows(6)->label('Descrição'),
                                Forms\Components\Repeater::make('itinerary_pt')->label('Roteiro')->schema([
                                    Forms\Components\TextInput::make('time')->label('Hora'),
                                    Forms\Components\TextInput::make('title')->label('Título'),
                                    Forms\Components\Textarea::make('description')->label('Descrição')->rows(2),
                                    Forms\Components\FileUpload::make('image')
                                        ->label('Imagem de referência')
                                        ->image()
                                        ->disk('public')
                                        ->directory('itinerary')
                                        ->imageEditor()
                                        ->maxSize(4096)
                                        ->saveUploadedFileUsing(ImageOptimizer::saver('itinerary', 1600))
                                        ->helperText('Opcional. Otimizada automaticamente para WebP. Se deixada vazia, a parada é exibida sem foto.')
                                        ->columnSpanFull(),
                                ])->columns(3)->collapsible()->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                Forms\Components\TagsInput::make('includes_pt')->label('Inclui')->placeholder('Adicionar item'),
                                Forms\Components\TagsInput::make('excludes_pt')->label('Não inclui')->placeholder('Adicionar item'),
                                Forms\Components\Textarea::make('recommendations_pt')->rows(3)->label('Recomendações'),
                                Forms\Components\Textarea::make('notes_pt')->rows(3)->label('Notas importantes'),
                                Forms\Components\Repeater::make('faqs_pt')->label('Perguntas frequentes')->schema([
                                    Forms\Components\TextInput::make('question')->label('Pergunta')->required(),
                                    Forms\Components\Textarea::make('answer')->label('Resposta')->rows(3)->required(),
                                ])->collapsible()->reorderable()->defaultItems(0)
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                    ->helperText('Exibido como acordeão na página do tour. Se vazio, usa o espanhol.'),
                            ]),

                        Tabs\Tab::make('Comparativa')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Forms\Components\Toggle::make('comparison.enabled')
                                    ->label('Mostrar bloque comparativo en la página del tour')
                                    ->helperText('Compara este tour ("experiencia premium") contra un tour convencional parecido, para que el visitante entienda la diferencia.')
                                    ->default(false)
                                    ->live(),
                                Forms\Components\Group::make()
                                    ->visible(fn (Forms\Get $get): bool => (bool) $get('comparison.enabled'))
                                    ->schema([
                                        Forms\Components\Select::make('comparison.color')
                                            ->label('Color del fondo')
                                            ->options([
                                                'teal' => 'Teal oscuro + acento naranja (recomendado)',
                                                'orange' => 'Naranja cálido (atardecer)',
                                            ])
                                            ->default('teal')
                                            ->native(false),
                                        Forms\Components\Tabs::make('comparison_lang')
                                            ->tabs([
                                                Forms\Components\Tabs\Tab::make('Español')->schema(
                                                    static::comparisonLocaleFields('es', true)
                                                ),
                                                Forms\Components\Tabs\Tab::make('English')->schema(
                                                    static::comparisonLocaleFields('en', false)
                                                ),
                                                Forms\Components\Tabs\Tab::make('Português')->schema(
                                                    static::comparisonLocaleFields('pt', false)
                                                ),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Imágenes')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('cover_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('tours/covers')
                                    ->imageEditor()
                                    ->maxSize(4096)
                                    ->saveUploadedFileUsing(ImageOptimizer::saver('tours/covers', 1600, deletePrevious: true))
                                    ->helperText('Se optimiza automáticamente a WebP (máx. 1600px de ancho). Tamaño máximo por archivo: 4 MB.')
                                    ->label('Imagen de portada'),
                                Forms\Components\FileUpload::make('gallery')
                                    ->multiple()
                                    ->image()
                                    ->disk('public')
                                    ->directory('tours/gallery')
                                    ->reorderable()
                                    ->panelLayout('grid')
                                    ->maxSize(4096)
                                    ->saveUploadedFileUsing(ImageOptimizer::saver('tours/gallery', 1920))
                                    ->helperText('Cada imagen se optimiza a WebP (máx. 1920px de ancho). Tamaño máximo por archivo: 4 MB.')
                                    ->label('Galería'),
                            ]),

                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('seo_title')
                                    ->maxLength(70)
                                    ->helperText('Recomendado: 50-60 caracteres')
                                    ->label('Title (SEO)'),
                                Forms\Components\Textarea::make('seo_description')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->helperText('Recomendado: 150-160 caracteres')
                                    ->label('Meta description'),
                                Forms\Components\TagsInput::make('seo_keywords')->label('Keywords'),
                                Forms\Components\FileUpload::make('seo_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('tours/seo')
                                    ->imageEditor()
                                    ->saveUploadedFileUsing(ImageOptimizer::saver('tours/seo', 1200, deletePrevious: true))
                                    ->helperText('Imagen Open Graph — se optimiza a WebP (máx. 1200px).')
                                    ->label('OG Image'),
                            ]),
                    ]),
            ]);
    }

    /**
     * Campo de precio "comma-safe" (docs/qa/F7-personas.md §g #2).
     *
     * Antes usaba ->numeric(), que Filament renderiza como <input type="number">.
     * Ese tipo de input descarta la coma en cuanto se teclea (no es un carácter
     * válido para un number input en navegadores en-US), así que "120,50"
     * llegaba al servidor ya mutilado como "12050" — nunca hubo oportunidad de
     * normalizarlo después, porque la información ya se había perdido en el
     * navegador. La solución es dejar de usar type="number" para este campo:
     * un <input type="text"> con inputmode="decimal" no bloquea la coma,
     * mantiene el teclado numérico en móvil, y delega la validación de formato
     * a la regla explícita de abajo (Tour::normalizePriceInput) en vez de al
     * navegador.
     */
    protected static function priceField(string $name): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)
            ->type('text')
            ->inputMode('decimal')
            ->prefix(\App\Support\Money::prefix(\App\Support\Money::site()))
            ->rule(function () use ($name) {
                return function (string $attribute, $value, \Closure $fail) use ($name) {
                    if ($value === null || $value === '') {
                        return; // "required" (cuando aplica) ya cubre el campo vacío
                    }

                    if (Tour::normalizePriceInput($value) === null) {
                        $fail('El precio debe ser un número válido. Usa punto o coma para los decimales (ej. 120.50 o 120,50).');

                        return;
                    }

                    if ($name === 'price' && Tour::normalizePriceInput($value) <= 0) {
                        $fail('El precio debe ser mayor que 0.');
                    }
                };
            })
            ->dehydrateStateUsing(fn ($state) => Tour::normalizePriceInput($state))
            ->reactive();
    }

    /**
     * Campos del bloque comparativo para un idioma. Solo español es obligatorio;
     * inglés y portugués caen a español si se dejan vacíos.
     */
    protected static function comparisonLocaleFields(string $loc, bool $primary): array
    {
        $hint = $primary ? null : 'Opcional. Si lo dejas vacío se usa el español.';

        return [
            Forms\Components\TextInput::make("comparison.badge_{$loc}")
                ->label('Etiqueta superior')
                ->placeholder('EXPERIENCIA EXCLUSIVA')
                ->maxLength(60)
                ->helperText($hint),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make("comparison.title_{$loc}")
                    ->label('Título (parte en blanco)')
                    ->placeholder('La única experiencia que incluye')
                    ->maxLength(120),
                Forms\Components\TextInput::make("comparison.title_hl_{$loc}")
                    ->label('Título (parte resaltada en naranja)')
                    ->placeholder('atardecer y picnic en el desierto')
                    ->maxLength(120),
            ]),
            Forms\Components\Textarea::make("comparison.intro_{$loc}")
                ->label('Párrafo introductorio')
                ->placeholder('No todos los tours se quedan para vivir el momento más mágico del día...')
                ->rows(3),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make("comparison.conv_title_{$loc}")
                    ->label('Título columna izquierda')
                    ->placeholder('TOUR CONVENCIONAL')
                    ->maxLength(60),
                Forms\Components\TextInput::make("comparison.prem_title_{$loc}")
                    ->label('Título columna derecha')
                    ->placeholder('NUESTRA EXPERIENCIA PREMIUM')
                    ->maxLength(60),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TagsInput::make("comparison.conv_{$loc}")
                    ->label('Ítems tour convencional (❌)')
                    ->placeholder('Agregar ítem')
                    ->helperText('Lo que ofrece el tour parecido/genérico.'),
                Forms\Components\TagsInput::make("comparison.prem_{$loc}")
                    ->label('Ítems experiencia premium (✓)')
                    ->placeholder('Agregar ítem')
                    ->helperText('Lo que hace único a ESTE tour.'),
            ]),
            Forms\Components\Textarea::make("comparison.footer_{$loc}")
                ->label('Frase destacada final')
                ->placeholder('El 95% de los viajeros se pierde el atardecer en Huacachina. Tú no seas uno de ellos.')
                ->rows(2),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->getStateUsing(fn ($record) => ImagePath::url($record->cover_image))
                    ->square()
                    ->size(60)
                    ->label(''),
                Tables\Columns\TextColumn::make('title_es')
                    ->searchable()->limit(40)
                    ->label('Título'),
                Tables\Columns\TextColumn::make('region.name_es')
                    ->badge()->color('info')
                    ->label('Región'),
                Tables\Columns\TextColumn::make('category.name_es')
                    ->badge()->color('gray')
                    ->label('Categoría'),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => $state === null ? null : \App\Support\Money::format((float) $state, \App\Support\Money::site(), 2))->sortable()
                    ->label('Precio'),
                Tables\Columns\IconColumn::make('has_offer')
                    ->label('Oferta')
                    ->getStateUsing(fn ($record): bool => $record->hasActiveOffer())
                    ->boolean()
                    ->trueIcon('heroicon-o-tag')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record): string => $record->hasActiveOffer()
                        ? 'OFERTA ESPECIAL -'.$record->discountPercent().'%'
                        : 'Sin oferta'
                    ),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric(decimalPlaces: 1)->sortable()
                    ->label('★'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Destacado'),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Publicado'),
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label('#'),
                Tables\Columns\TextInputColumn::make('featured_order')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:1'])
                    ->sortable()
                    ->label('# Más Comprados')
                    ->tooltip('Posición en "Más Comprados" del home: 1 = primero, 2 = segundo… Deja vacío para que se ordene solo por número de reservas.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('region_id')
                    ->relationship('region', 'name_es')
                    ->label('Región'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name_es')
                    ->label('Categoría'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Publicado'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Destacado'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->reorderable('order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
