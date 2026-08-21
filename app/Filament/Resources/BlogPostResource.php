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

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Contenido';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Artículos';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('BlogPost')
                    ->columnSpanFull()
                    ->tabs([

                        // ── Spanish content ──────────────────────────────
                        Tabs\Tab::make('Español')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_es')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Título'),
                                Forms\Components\Textarea::make('excerpt_es')
                                    ->required()
                                    ->rows(3)
                                    ->label('Extracto'),
                                Forms\Components\RichEditor::make('body_es')
                                    ->required()
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('blog/attachments')
                                    ->label('Cuerpo del artículo'),
                                // Cita destacada (docs/rebrand/inventario/spec-03-blog.md §5.2, §9):
                                // traducible como el resto del contenido. La atribución ("– Nombre,
                                // Rol") vive en la pestaña "Datos" sin variante de idioma.
                                Forms\Components\Textarea::make('quote_text_es')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->label('Cita destacada')
                                    ->helperText('Vacío = no se muestra ninguna cita destacada en el artículo.'),
                            ]),

                        // ── English content ───────────────────────────────
                        Tabs\Tab::make('English')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_en')
                                    ->maxLength(255)
                                    ->label('Title'),
                                Forms\Components\Textarea::make('excerpt_en')
                                    ->rows(3)
                                    ->label('Excerpt'),
                                Forms\Components\RichEditor::make('body_en')
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('blog/attachments')
                                    ->label('Body'),
                                Forms\Components\Textarea::make('quote_text_en')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->label('Pull quote')
                                    ->helperText('Empty = falls back to the Spanish quote, or hides if that is empty too.'),
                            ]),

                        // ── Portuguese content ────────────────────────────
                        Tabs\Tab::make('Português')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Forms\Components\TextInput::make('title_pt')
                                    ->maxLength(255)
                                    ->label('Título'),
                                Forms\Components\Textarea::make('excerpt_pt')
                                    ->rows(3)
                                    ->label('Extracto'),
                                Forms\Components\RichEditor::make('body_pt')
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('blog/attachments')
                                    ->label('Corpo do artigo'),
                                Forms\Components\Textarea::make('quote_text_pt')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->label('Citação destacada')
                                    ->helperText('Vazio = usa a citação em espanhol, ou se oculta se essa também estiver vazia.'),
                            ]),

                        // ── Cover image ───────────────────────────────────
                        Tabs\Tab::make('Imagen')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('cover_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog/covers')
                                    ->imageEditor()
                                    ->nullable()
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('blog/covers', 1600, deletePrevious: true))
                                    ->helperText('Se optimiza automáticamente a WebP (máx. 1600px de ancho).')
                                    ->label('Imagen de portada'),
                                // Avatar de la línea de firma (docs/rebrand/inventario/02-tour-y-blog.md
                                // §2.B #9): campo simple en blog_posts, sin FK a Guide (esa unificación
                                // queda para otra tarea). Nullable: sin foto, la firma se muestra sin
                                // avatar en vez de un silueta genérica.
                                Forms\Components\FileUpload::make('author_photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog/authors')
                                    ->imageEditor()
                                    ->nullable()
                                    ->maxSize(2048)
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('blog/authors', 400, deletePrevious: true))
                                    ->helperText('Avatar del autor en la línea de firma. Se optimiza a WebP (máx. 400px). Vacío = la firma se muestra sin foto. Se ignora si hay un "Guía real" seleccionado en la pestaña Datos: en ese caso manda la foto del guía.')
                                    ->label('Foto del autor'),
                                // Botón de play sobre el hero (spec-03-blog.md §1, §9): mismo patrón
                                // que Tour.video_url, reutilizando VideoEmbed::normalize().
                                Forms\Components\TextInput::make('video_url')
                                    ->label('URL del video')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://www.youtube.com/watch?v=...')
                                    ->helperText('Pega el enlace para compartir (YouTube, youtu.be, Shorts o Vimeo). Se convierte automáticamente al formato que sí se puede incrustar. Vacío = sin botón de video sobre el hero.'),
                            ]),

                        // ── Features (tarjeta sobre el hero) ──────────────
                        Tabs\Tab::make('Features')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                // Tarjeta blanca de 4 bloques que se monta sobre el hero
                                // (spec-03-blog.md §1, §2, §9). maxItems(4) porque el mockup
                                // define 4 columnas, pero se puede publicar con menos: el
                                // accesor BlogPost::feature_cards filtra las filas sin título
                                // y la tarjeta debe verse bien con 0, 2, 3 o 4 bloques.
                                Forms\Components\Repeater::make('features')
                                    ->label('Bloques de la tarjeta')
                                    ->helperText('Hasta 4 bloques (ícono + título + texto). Podés cargar menos de 4: los que falten simplemente no se muestran, no hace falta completarlos todos.')
                                    ->minItems(0)
                                    ->maxItems(4)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title_es'] ?? null)
                                    ->schema([
                                        Forms\Components\TextInput::make('icon')
                                            ->label('Ícono')
                                            ->maxLength(255)
                                            ->helperText('Nombre del ícono del set que ya usa el sitio (mismo criterio que Categoría → Ícono).'),
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('title_es')->label('Título (ES)')->maxLength(255),
                                            Forms\Components\TextInput::make('title_en')->label('Title (EN)')->maxLength(255),
                                            Forms\Components\TextInput::make('title_pt')->label('Título (PT)')->maxLength(255),
                                        ]),
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Textarea::make('text_es')->label('Texto (ES)')->rows(2),
                                            Forms\Components\Textarea::make('text_en')->label('Text (EN)')->rows(2),
                                            Forms\Components\Textarea::make('text_pt')->label('Texto (PT)')->rows(2),
                                        ]),
                                    ]),
                            ]),

                        // ── Post data ─────────────────────────────────────
                        Tabs\Tab::make('Datos')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255)
                                    ->helperText('Se genera automáticamente del título en español si se deja vacío.')
                                    ->label('Slug (URL)'),
                                Forms\Components\TextInput::make('category')
                                    ->maxLength(255)
                                    ->label('Categoría'),
                                Forms\Components\TagsInput::make('tags')
                                    ->placeholder('Agregar etiqueta')
                                    ->label('Etiquetas'),
                                // Autor real (spec-03-blog.md §5.1, §9): si se elige un guía acá,
                                // su nombre/rol/foto reemplazan por completo a los tres campos
                                // sueltos de abajo en la firma pública (nunca se mezclan campo por
                                // campo) y aparece el check de "verificado". Dejar vacío es lo normal
                                // hoy: ningún post tiene un guía real asignado todavía.
                                Forms\Components\Select::make('guide_id')
                                    ->relationship('guide', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->label('Guía real (autor)')
                                    ->helperText('Si seleccionás un guía, su foto/nombre/rol de "Nuestro Equipo" reemplazan a los 3 campos de abajo en la firma del artículo, y se muestra el check de verificado. Vacío = se usa Nombre/Rol/Foto del autor tal como estén.'),
                                Forms\Components\TextInput::make('author_name')
                                    ->maxLength(255)
                                    ->label('Nombre del autor (respaldo)')
                                    ->helperText('Se usa solo si no hay "Guía real" seleccionado arriba. Vacío = se usa el nombre del sitio (Configuración → General) como firma.'),
                                // Rol bajo el nombre en la línea de firma (docs/rebrand/inventario/02-tour-y-blog.md
                                // §2.B #9), ej. "Guía Local". Sin FK a Guide: campo de texto simple.
                                Forms\Components\TextInput::make('author_role')
                                    ->maxLength(255)
                                    ->label('Rol del autor (respaldo)')
                                    ->placeholder('Guía Local')
                                    ->helperText('Se usa solo si no hay "Guía real" seleccionado arriba. Vacío = no se muestra ningún rol.'),
                                Forms\Components\TextInput::make('quote_attribution')
                                    ->maxLength(255)
                                    ->label('Atribución de la cita destacada')
                                    ->placeholder('— Augusto, Guía Local')
                                    ->helperText('Línea que acompaña a la cita destacada de cada pestaña de idioma. Vacío = la cita se muestra sin firma.'),
                                Forms\Components\TextInput::make('reading_minutes')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(120)
                                    ->label('Minutos de lectura')
                                    ->helperText('Se calcula automático a partir del cuerpo en español si se deja vacío. Puedes forzar un número propio aquí.'),
                            ]),

                        // ── SEO ───────────────────────────────────────────
                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title_es')
                                    ->maxLength(70)
                                    ->helperText('Recomendado: 50-60 caracteres')
                                    ->label('Meta title (Español)'),
                                Forms\Components\Textarea::make('meta_description_es')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->helperText('Recomendado: 150-160 caracteres')
                                    ->label('Meta description (Español)'),

                                Forms\Components\TextInput::make('meta_title_en')
                                    ->maxLength(70)
                                    ->helperText('Recommended: 50-60 characters')
                                    ->label('Meta title (English)'),
                                Forms\Components\Textarea::make('meta_description_en')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->label('Meta description (English)'),

                                Forms\Components\TextInput::make('meta_title_pt')
                                    ->maxLength(70)
                                    ->label('Meta title (Português)'),
                                Forms\Components\Textarea::make('meta_description_pt')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->label('Meta description (Português)'),
                            ]),

                        // ── Publication settings ──────────────────────────
                        Tabs\Tab::make('Publicación')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                // docs/qa/F7-personas.md §labels #7 / §g #11: mismo criterio que ahora usa
                                // TourResource (apagado = borrador por defecto), con el mismo texto de ayuda.
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Publicado')
                                    ->default(false)
                                    ->helperText('Actívalo para que se vea en la web.'),
                                Forms\Components\DateTimePicker::make('published_at')
                                    ->nullable()
                                    ->helperText('Deja vacío para publicar inmediatamente al activar el toggle.')
                                    ->label('Fecha de publicación'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title_es')
                    ->searchable()
                    ->limit(50)
                    ->label('Título (ES)'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->label('Categoría'),
                Tables\Columns\ToggleColumn::make('is_published')
                    ->label('Publicado'),
                Tables\Columns\TextColumn::make('published_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('reading_minutes')
                    ->suffix(' min')
                    ->label('Lectura'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Publicado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
