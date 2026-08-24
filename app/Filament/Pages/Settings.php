<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración del sitio';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $rows = Setting::all()->pluck('value', 'key')->toArray();
        // Pre-rellenar credenciales de PayPal desde el .env si aún no están en la BD
        $rows['paypal_client_id'] = $rows['paypal_client_id'] ?? config('services.paypal.client_id');
        $rows['paypal_secret'] = $rows['paypal_secret'] ?? config('services.paypal.secret');
        $rows['paypal_mode'] = $rows['paypal_mode'] ?? config('services.paypal.mode', 'sandbox');
        $rows['paypal_webhook_id'] = $rows['paypal_webhook_id'] ?? config('services.paypal.webhook_id');

        // Culqi y moneda del sitio: mismo criterio, .env como respaldo.
        $rows['culqi_public_key'] = $rows['culqi_public_key'] ?? config('services.culqi.public_key');
        $rows['culqi_secret_key'] = $rows['culqi_secret_key'] ?? config('services.culqi.secret_key');
        $rows['culqi_env'] = $rows['culqi_env'] ?? config('services.culqi.env', 'sandbox');
        $rows['site_currency'] = $rows['site_currency'] ?? config('services.site_currency', 'USD');

        // Pre-rellenar keys de Google y Tripadvisor desde .env si aún no están en la BD
        $rows['google_maps_api_key'] = $rows['google_maps_api_key'] ?? config('services.google.maps_api_key');
        $rows['google_place_id'] = $rows['google_place_id'] ?? config('services.google.place_id');
        $rows['google_reviews_enabled'] = isset($rows['google_reviews_enabled'])
            ? filter_var($rows['google_reviews_enabled'], FILTER_VALIDATE_BOOLEAN)
            : false;
        $rows['tripadvisor_api_key'] = $rows['tripadvisor_api_key'] ?? config('services.tripadvisor.api_key');
        $rows['tripadvisor_location_id'] = $rows['tripadvisor_location_id'] ?? config('services.tripadvisor.location_id');
        $rows['tripadvisor_reviews_enabled'] = isset($rows['tripadvisor_reviews_enabled'])
            ? filter_var($rows['tripadvisor_reviews_enabled'], FILTER_VALIDATE_BOOLEAN)
            : false;

        // Pre-rellenar Zonas de recogida (pickup) — configuración GLOBAL para todos los tours
        $rows['pickup_enabled'] = isset($rows['pickup_enabled'])
            ? filter_var($rows['pickup_enabled'], FILTER_VALIDATE_BOOLEAN)
            : false;

        // Pre-rellenar reCAPTCHA desde .env si aún no están en la BD
        $rows['recaptcha_enabled'] = isset($rows['recaptcha_enabled'])
            ? filter_var($rows['recaptcha_enabled'], FILTER_VALIDATE_BOOLEAN)
            : (bool) config('services.recaptcha.enabled', false);
        $rows['recaptcha_version'] = $rows['recaptcha_version'] ?? config('services.recaptcha.version', 'v3');
        $rows['recaptcha_site_key'] = $rows['recaptcha_site_key'] ?? config('services.recaptcha.site_key');
        $rows['recaptcha_secret_key'] = $rows['recaptcha_secret_key'] ?? config('services.recaptcha.secret_key');
        $rows['recaptcha_v3_threshold'] = $rows['recaptcha_v3_threshold'] ?? config('services.recaptcha.threshold', 0.5);

        // Pre-fill cookie banner settings with defaults if not yet stored
        $rows['cookie_banner_enabled'] = isset($rows['cookie_banner_enabled'])
            ? filter_var($rows['cookie_banner_enabled'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $rows['cookie_text_es'] = $rows['cookie_text_es'] ?? '';
        $rows['cookie_text_en'] = $rows['cookie_text_en'] ?? '';
        $rows['cookie_text_pt'] = $rows['cookie_text_pt'] ?? '';

        // Pre-fill new hero stats bar + newsletter block toggles (mockup 2026-08)
        $rows['home_stats_enabled'] = isset($rows['home_stats_enabled'])
            ? filter_var($rows['home_stats_enabled'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $rows['home_news_enabled'] = isset($rows['home_news_enabled'])
            ? filter_var($rows['home_news_enabled'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $rows['about_stats_enabled'] = isset($rows['about_stats_enabled'])
            ? filter_var($rows['about_stats_enabled'], FILTER_VALIDATE_BOOLEAN)
            : true;

        // Decode FAQs JSON for the Repeater
        if (isset($rows['faqs']) && is_string($rows['faqs'])) {
            $decoded = json_decode($rows['faqs'], true);
            $rows['faqs'] = is_array($decoded) ? $decoded : [];
        } else {
            $rows['faqs'] = [];
        }

        // Decode home Repeaters
        $repeaterKeys = [
            'home_destinos', 'home_why_items', 'home_tour_type_tabs',
            'home_footer_features', 'home_exp_tours', 'home_reco_items', 'home_faqs',
            'pickup_zones',
        ];
        foreach ($repeaterKeys as $rk) {
            if (isset($rows[$rk]) && is_string($rows[$rk])) {
                $decoded = json_decode($rows[$rk], true);
                $rows[$rk] = is_array($decoded) ? $decoded : [];
            } else {
                $rows[$rk] = $rows[$rk] ?? [];
            }
        }

        $this->form->fill($rows);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')->columnSpanFull()->tabs([
                    Tabs\Tab::make('General')->icon('heroicon-o-globe-alt')->schema([
                        TextInput::make('site_name')->label('Nombre del sitio'),
                        TextInput::make('site_tagline_es')->label('Frase corta / Eslogan (ES)'),
                        TextInput::make('site_tagline_en')->label('Frase corta / Eslogan (EN)'),
                        Textarea::make('site_description_es')->rows(2)->label('Descripción (ES)'),
                        Textarea::make('site_description_en')->rows(2)->label('Descripción (EN)'),
                    ]),
                    Tabs\Tab::make('Contacto')->icon('heroicon-o-phone')->schema([
                        TextInput::make('contact_email')->email()->label('Correo de contacto'),
                        TextInput::make('contact_phone')->label('Teléfono principal'),
                        TextInput::make('contact_phone_secondary')->label('Teléfono secundario'),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp (sin +)')
                            ->placeholder('51900000000')
                            ->helperText('Solo números: código de país + número, sin espacios ni "+". Ejemplo de formato (no es un número real): 51900000000.'),
                        TextInput::make('contact_address_es')
                            ->label('Dirección (ES)')
                            ->helperText('Única fuente de la dirección en TODO el sitio (footer, JSON-LD/SEO). Vacío = el bloque de dirección se oculta en vez de mostrar un dato sin confirmar.'),
                        TextInput::make('contact_address_en')->label('Dirección (EN)'),
                        TextInput::make('contact_address_pt')->label('Endereço (PT)'),
                        TextInput::make('contact_hours_es')
                            ->label('Horarios (ES)')
                            ->helperText('Única fuente del horario en TODO el sitio (footer, ficha de contacto, Términos). Vacío = el bloque de horario se oculta.'),
                        TextInput::make('contact_hours_en')->label('Horarios (EN)'),
                        TextInput::make('contact_hours_pt')->label('Horários (PT)'),

                        // ── Datos legales ──────────────────────────────────────
                        // 2026-08-11: el footer y los Términos llegaron a publicar
                        // DOS RUC contradictorios de manera simultánea (uno de "Viaja
                        // con LAT S.A.C.", otro de una persona natural), ninguno
                        // confirmado por el cliente. Única fuente ahora — vacío hasta
                        // que el cliente confirme cuál es el correcto.
                        \Filament\Forms\Components\Fieldset::make('Datos legales')
                            ->columns(2)
                            ->schema([
                                TextInput::make('company_ruc')
                                    ->label('RUC')
                                    ->helperText('Sin confirmar todavía. No escribas un RUC que no puedas verificar con el cliente — vacío es mejor que uno equivocado.'),
                                TextInput::make('company_legal_name')
                                    ->label('Razón social'),

                                // Sellos oficiales — pedido del jefe el 2026-08-21
                                // ("un logotipo que nos pide la municipalidad de Lima"
                                // y "ESNNA también"). Son ARCHIVOS DEL CLIENTE: no se
                                // redibujan acá. Vacío = el footer no pinta el sello,
                                // en vez de mostrar un cuadro roto o un dibujo propio
                                // que parecería un sello oficial sin serlo.
                                FileUpload::make('company_registry_seal')
                                    ->label('Sello "Agencia de viajes y turismo registrada"')
                                    ->image()
                                    ->disk('media')
                                    ->directory('legal')
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('legal', 480, disk: 'media', deletePrevious: true))
                                    ->helperText('El sello oficial que entrega la autoridad (MINCETUR / Municipalidad). Súbelo tal como te lo dieron, con fondo transparente si se puede. Se muestra en el footer de todas las páginas.')
                                    ->columnSpanFull(),
                                TextInput::make('company_registry_seal_url')
                                    ->label('Enlace de verificación del sello')
                                    ->url()
                                    ->helperText('Opcional. Si el registro tiene una ficha pública en línea, ponla acá y el sello del footer se vuelve un enlace comprobable.')
                                    ->columnSpanFull(),
                                FileUpload::make('esnna_seal')
                                    ->label('Sello ESNNA')
                                    ->image()
                                    ->disk('media')
                                    ->directory('legal')
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('legal', 480, disk: 'media', deletePrevious: true))
                                    ->helperText('Opcional. La página "Código de conducta ESNNA" del sitio funciona igual sin este sello: el compromiso se publica como texto y el footer siempre enlaza a esa página.')
                                    ->columnSpanFull(),
                            ]),

                        TextInput::make('booking_notification_email')
                            ->label('Emails para avisos de reserva')
                            ->placeholder('correo1@dominio.com, correo2@dominio.com')
                            ->helperText('Cada vez que entre una nueva reserva se enviará un aviso a estas direcciones. Puedes poner VARIOS correos separados por coma. El cliente que reserva siempre recibe su confirmación aparte. Si se deja vacío se usará el correo remitente del servidor.')
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    foreach (preg_split('/[,;\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $email) {
                                        if (! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                                            $fail("«{$email}» no es un correo válido.");
                                        }
                                    }
                                };
                            }),

                        // ── Footer: descripción de la empresa ─────────────────
                        \Filament\Forms\Components\Section::make('Footer — descripción de la empresa')
                            ->description('Texto que aparece bajo el logo en el footer. Dejar vacío para usar el texto por defecto del idioma.')
                            ->collapsible()
                            ->schema([
                                Textarea::make('footer_about_es')
                                    ->label('Descripción footer (ES)')
                                    ->rows(3)
                                    ->placeholder('Nuestra empresa se distingue por ofrecer experiencias únicas y vibrantes…')
                                    ->columnSpanFull(),
                                Textarea::make('footer_about_en')
                                    ->label('Descripción footer (EN)')
                                    ->rows(3)
                                    ->placeholder('Our company distinguishes itself by offering unique and vibrant experiences…')
                                    ->columnSpanFull(),
                                Textarea::make('footer_about_pt')
                                    ->label('Descripción footer (PT)')
                                    ->rows(3)
                                    ->placeholder('Nossa empresa se distingue por oferecer experiências únicas e vibrantes…')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                    Tabs\Tab::make('Redes sociales')->icon('heroicon-o-share')->schema([
                        TextInput::make('social_instagram')->prefix('https://')->label('Instagram'),
                        TextInput::make('social_facebook')->prefix('https://')->label('Facebook'),
                        TextInput::make('social_tiktok')->prefix('https://')->label('TikTok'),
                        TextInput::make('social_youtube')->prefix('https://')->label('YouTube'),
                        TextInput::make('social_google_reviews')
                            ->label('Enlace "Ver en Google" (reseñas)')
                            ->url()
                            ->helperText('Enlace a la ficha de Google Business para dejar/ver reseñas. Usado en las tarjetas de rating de cada tour.'),
                        TextInput::make('social_tripadvisor')
                            ->label('Enlace "Ver en Tripadvisor"')
                            ->url()
                            ->helperText('Enlace al perfil de Tripadvisor. Usado en las tarjetas de rating de cada tour Y en el bloque de reseñas del footer (abajo).'),

                        // Bloque de reseñas del footer — pedido del jefe el
                        // 2026-08-21 ("sería bueno tener nuestro icono de
                        // Tripadvisor para reseñas"), con la referencia de
                        // incatrilogytours: logo + estrellas + 4.9 + "3,968
                        // reseñas · #1 en Lima".
                        //
                        // El guard vive en App\Support\TripadvisorBadge y es TODO
                        // O NADA: sin enlace, sin rating o sin cantidad, el bloque
                        // no se pinta. Este repo ya publicó un "4.8 (0 reseñas)"
                        // sembrado en los 24 tours; un default acá sería una reseña
                        // falsa en el footer de todo el sitio.
                        \Filament\Forms\Components\Fieldset::make('Bloque de reseñas de Tripadvisor (footer)')
                            ->columns(2)
                            ->schema([
                                TextInput::make('tripadvisor_rating')
                                    ->label('Calificación')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->placeholder('4.9')
                                    ->helperText('Tal como figura en el perfil, de 1 a 5.'),
                                TextInput::make('tripadvisor_reviews_count')
                                    ->label('Cantidad de opiniones')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('3968')
                                    ->helperText('Solo el número, sin comas ni texto.'),
                                \Filament\Forms\Components\Placeholder::make('tripadvisor_badge_note')
                                    ->label('')
                                    ->content('El bloque del footer aparece solo si están los tres datos: enlace al perfil (arriba), calificación y cantidad de opiniones. Con uno vacío no se muestra nada — es a propósito: una cifra sin enlace verificable no se distingue de una inventada.')
                                    ->columnSpanFull(),
                                TextInput::make('tripadvisor_rank_es')
                                    ->label('Posición (ES)')
                                    ->placeholder('#1 en Lima')
                                    ->helperText('Opcional. Se puede vaciar sin que desaparezca el bloque: es un dato que cambia solo en Tripadvisor, así que solo se publica si alguien lo mantiene al día.'),
                                TextInput::make('tripadvisor_rank_en')->label('Posición (EN)')->placeholder('#1 in Lima'),
                                TextInput::make('tripadvisor_rank_pt')->label('Posición (PT)')->placeholder('#1 em Lima'),
                            ]),
                    ]),
                    Tabs\Tab::make('Pagos')->icon('heroicon-o-credit-card')->schema([
                        // Moneda del sitio: la lee App\Support\Money::site() y con
                        // ella se pintan TODOS los precios, se cobra en Culqi/PayPal
                        // y se graba bookings.currency. Cambiarla aquí no re-tarifa
                        // los tours: los números guardados no se tocan, solo cambia
                        // la moneda con la que se leen y se cobran.
                        Select::make('site_currency')
                            ->label('Moneda del sitio')
                            ->options(['USD' => 'USD (Dólares)', 'PEN' => 'PEN (Soles)'])
                            ->default('USD')
                            ->native(false)
                            ->required()
                            ->rules(['in:USD,PEN'])
                            ->helperText('Moneda en la que se muestran los precios y se cobra. OJO: no convierte los precios ya cargados — si cambias a soles, el número 720 pasa de $720 a S/ 720. PayPal NO admite soles: en PEN solo queda la tarjeta (Culqi).'),
                        Select::make('paypal_mode')
                            ->label('Modo de PayPal')
                            ->options(['sandbox' => 'Sandbox (pruebas)', 'live' => 'Live (producción)'])
                            ->default('sandbox')
                            ->native(false)
                            ->required()
                            ->helperText('Sandbox = pruebas sin dinero real. Live = cobros reales. Cambia a "Live" solo con credenciales de producción.'),
                        TextInput::make('paypal_client_id')
                            ->label('PayPal Client ID (público)')
                            ->columnSpanFull()
                            ->autocomplete(false),
                        TextInput::make('paypal_secret')
                            ->label('PayPal Secret (privado)')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->autocomplete('new-password')
                            ->helperText('Se guarda en la base de datos. Si lo cambias en PayPal, actualízalo aquí.'),
                        TextInput::make('paypal_webhook_id')
                            ->label('Webhook ID (opcional)'),
                        // Culqi: mismo criterio que PayPal — administrable aquí, con
                        // el .env como respaldo (ver App\Services\PaymentService).
                        // Las claves de PRUEBA empiezan por pk_test_/sk_test_; las de
                        // producción por pk_live_/sk_live_.
                        Select::make('culqi_env')
                            ->label('Modo de Culqi (tarjeta)')
                            ->options(['sandbox' => 'Sandbox (pruebas)', 'live' => 'Live (producción)'])
                            ->default('sandbox')
                            ->native(false)
                            ->helperText('Sandbox = pruebas sin dinero real, con las claves pk_test_/sk_test_.'),
                        TextInput::make('culqi_public_key')
                            ->label('Culqi Public Key (pk_...)')
                            ->columnSpanFull()
                            ->autocomplete(false)
                            ->helperText('Viaja al navegador para tokenizar la tarjeta: es pública a propósito.'),
                        TextInput::make('culqi_secret_key')
                            ->label('Culqi Secret Key (sk_...)')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->autocomplete('new-password')
                            ->helperText('Privada: nunca sale del servidor. Se guarda en la base de datos.'),
                    ]),
                    Tabs\Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                        TextInput::make('seo_default_title')->label('Title por defecto'),
                        Textarea::make('seo_default_description')->rows(2)->maxLength(160)->label('Description por defecto'),
                        TextInput::make('seo_default_keywords')->label('Keywords (separadas por coma)'),
                        FileUpload::make('seo_og_image')->image()->disk('public')->directory('seo')
                            ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('seo', 1200, deletePrevious: true))
                            ->helperText('Se optimiza a WebP (máx. 1200px).')
                            ->label('OG Image por defecto'),
                        TextInput::make('seo_google_site_verification')->label('Google Search Console'),
                        TextInput::make('seo_bing_site_verification')->label('Bing Webmaster'),
                        TextInput::make('seo_google_analytics_id')->placeholder('G-XXXXXX')->label('Google Analytics 4'),
                        TextInput::make('seo_gtm_id')->placeholder('GTM-XXXXXX')->label('Google Tag Manager'),
                        TextInput::make('seo_facebook_pixel')->label('Facebook Pixel ID'),
                    ]),
                    Tabs\Tab::make('Home')->icon('heroicon-o-home')->schema([

                        // ── TODAS las imágenes del Home ──────────────────────
                        // Un solo lugar para cambiar cada imagen del home. Los
                        // FileUpload son de nivel superior (dentro de Repeater no
                        // persisten en esta página). Vacío = imagen por defecto.
                        // Nota: las fotos de las tarjetas de tours ("Más Comprados",
                        // "Ofertas", etc.) se editan en cada Tour → pestaña Imágenes.
                        \Filament\Forms\Components\Section::make('🖼️ Imágenes del Home')
                            ->description('Aquí cambias TODAS las imágenes editables del home: hero, destinos, tipos de tour y experiencias. Cada foto se optimiza a WebP automáticamente. Vacío = imagen por defecto.')
                            ->collapsible()
                            ->schema([
                                \Filament\Forms\Components\Fieldset::make('Hero (banner superior)')
                                    ->schema([
                                        FileUpload::make('home_hero_image')
                                            ->label('Imagen de fondo del hero')
                                            ->image()->disk('media')->directory('home')
                                            ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1920, disk: 'media', deletePrevious: true))
                                            ->helperText('Dejar vacío para usar la imagen por defecto (Machu Picchu). Máx. 1920px.')
                                            ->columnSpanFull(),
                                    ])->columns(1),
                                \Filament\Forms\Components\Fieldset::make('Ciudades más visitadas (Destinos)')
                                    ->schema([
                                        FileUpload::make('home_destino_img_1')->label('1 · Tours en Cusco')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_destino_img_2')->label('2 · Tours en Lima')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_destino_img_3')->label('3 · Tours en Ica')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                    ])->columns(3),
                                \Filament\Forms\Components\Fieldset::make('¿Qué tipo de tour estás buscando?')
                                    ->schema([
                                        FileUpload::make('home_tourtype_img_1')->label('1 · Tours Culturales')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_tourtype_img_2')->label('2 · Tours de Aventura')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_tourtype_img_3')->label('3 · Experiencias Culinarias')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_tourtype_img_4')->label('4 · Otras experiencias')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                    ])->columns(2),
                                \Filament\Forms\Components\Fieldset::make('Descubre experiencias únicas')
                                    ->schema([
                                        FileUpload::make('home_exp_img_1')->label('1 · Experiencia')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_exp_img_2')->label('2 · Experiencia')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_exp_img_3')->label('3 · Experiencia')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_exp_img_4')->label('4 · Experiencia')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                    ])->columns(2),
                                \Filament\Forms\Components\Fieldset::make('Galería (Descubre la belleza del Perú)')
                                    ->schema([
                                        FileUpload::make('home_gallery_img_1')->label('1 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_gallery_img_2')->label('2 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_gallery_img_3')->label('3 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_gallery_img_4')->label('4 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_gallery_img_5')->label('5 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                        FileUpload::make('home_gallery_img_6')->label('6 · Foto')->image()->disk('media')->directory('home')->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1400, disk: 'media', deletePrevious: true)),
                                    ])->columns(3),
                            ]),

                        // ── Hero (títulos) ────────────────────────────────────
                        \Filament\Forms\Components\Section::make('Hero — títulos')
                            ->description('Título principal del banner superior (la imagen está arriba en "Imágenes del Home").')
                            ->collapsible()
                            ->schema([
                                Textarea::make('home_hero_title_es')
                                    ->label('Hero título (ES)')
                                    ->rows(3)
                                    ->helperText('Usa saltos de línea para controlar el quiebre del texto. Ej: "Descubre\nlo que te\ntransforma."')
                                    ->placeholder("Descubre\nlo que te\ntransforma.")
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_title_en')
                                    ->label('Hero título (EN)')
                                    ->rows(3)
                                    ->placeholder("Discover\nwhat\ntransforms you.")
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_title_pt')
                                    ->label('Hero título (PT)')
                                    ->rows(3)
                                    ->placeholder("Descubra\no que\ntransforma você.")
                                    ->columnSpanFull(),
                            ]),

                        // ── Hero — textos (SEO Fix 1, 2026-07-27): el hero rediseñado
                        // ya LEE estas claves con Setting::get('clave') ?: $default
                        // (home.blade.php), pero no existían campos aquí — el cliente
                        // veía siempre el default hardcodeado y no podía cambiar ni
                        // una palabra. Mismos nombres de clave que consume la vista. ──
                        \Filament\Forms\Components\Section::make('Hero — eyebrow, línea roja y párrafo')
                            ->description('Textos del banner superior, encima del bloque "10+ años" y la tarjeta de confianza. Deja vacío cualquier campo para usar el texto por defecto ya cargado en el diseño.')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_hero_eyebrow_es')->label('Eyebrow rojo pequeño (ES)')->placeholder('Somos'),
                                TextInput::make('home_hero_eyebrow_en')->label('Eyebrow rojo pequeño (EN)')->placeholder('We are'),
                                TextInput::make('home_hero_eyebrow_pt')->label('Eyebrow rojo pequeño (PT)')->placeholder('Somos'),

                                Textarea::make('home_hero_tagline_es')
                                    ->label('Línea roja, 2 líneas (ES)')
                                    ->rows(2)
                                    ->helperText('Usa un salto de línea para controlar dónde corta el texto. Ej: "10 años mostrando" + Enter + "lo mejor del Perú".')
                                    ->placeholder("10 años mostrando\nlo mejor del Perú")
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_tagline_en')
                                    ->label('Línea roja, 2 líneas (EN)')
                                    ->rows(2)
                                    ->placeholder("10 years showcasing\nthe best of Peru")
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_tagline_pt')
                                    ->label('Línea roja, 2 líneas (PT)')
                                    ->rows(2)
                                    ->placeholder("10 anos mostrando\no melhor do Peru")
                                    ->columnSpanFull(),

                                Textarea::make('home_hero_sub_es')
                                    ->label('Párrafo gris del hero (ES)')
                                    ->rows(2)
                                    ->placeholder('Explora lugares increíbles, vive experiencias únicas y crea recuerdos que durarán para siempre.')
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_sub_en')
                                    ->label('Párrafo gris del hero (EN)')
                                    ->rows(2)
                                    ->placeholder('Explore incredible places, live unique experiences and create memories that will last forever.')
                                    ->columnSpanFull(),
                                Textarea::make('home_hero_sub_pt')
                                    ->label('Párrafo gris del hero (PT)')
                                    ->rows(2)
                                    ->placeholder('Explore lugares incríveis, viva experiências únicas e crie memórias que vão durar para sempre.')
                                    ->columnSpanFull(),
                            ]),

                        // ── Hero — bloque "10+ años" ──────────────────────────
                        \Filament\Forms\Components\Section::make('Hero — bloque "10+ años"')
                            ->description('El número, la etiqueta y el subtexto del bloque de años de experiencia dentro del hero.')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_hero_years_number')
                                    ->label('Número (ej. "10+")')
                                    ->placeholder('10+')
                                    ->helperText('Campo único, sin idioma: el número se ve igual en los 3 idiomas.'),
                                TextInput::make('home_hero_years_label_es')->label('Etiqueta (ES)')->placeholder('Años de experiencia'),
                                TextInput::make('home_hero_years_label_en')->label('Etiqueta (EN)')->placeholder('Years of experience'),
                                TextInput::make('home_hero_years_label_pt')->label('Etiqueta (PT)')->placeholder('Anos de experiência'),
                                TextInput::make('home_hero_years_sub_es')->label('Subtexto (ES)')->placeholder('Miles de viajeros descubriendo el Perú')->columnSpanFull(),
                                TextInput::make('home_hero_years_sub_en')->label('Subtexto (EN)')->placeholder('Thousands of travelers discovering Peru')->columnSpanFull(),
                                TextInput::make('home_hero_years_sub_pt')->label('Subtexto (PT)')->placeholder('Milhares de viajantes descobrindo o Peru')->columnSpanFull(),
                            ]),

                        // ── Hero — tarjeta de confianza ────────────────────────
                        \Filament\Forms\Components\Section::make('Hero — tarjeta de confianza (4 textos + ícono)')
                            ->description('Los 4 textos cortos con ícono que aparecen en la tarjeta de confianza flotante del hero. El ícono se elige de la lista: si se deja vacío, queda el del diseño aprobado.')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                Select::make('home_hero_trust_1_icon')
                                    ->label('Confianza 1 — Ícono')
                                    ->options(\App\Support\HeroIcons::options())
                                    ->placeholder('Guía / personas (por defecto)')
                                    ->native(false),
                                TextInput::make('home_hero_trust_1_es')->label('Confianza 1 (ES)')->placeholder('Guías expertos locales'),
                                TextInput::make('home_hero_trust_1_en')->label('Confianza 1 (EN)')->placeholder('Local expert guides'),
                                TextInput::make('home_hero_trust_1_pt')->label('Confianza 1 (PT)')->placeholder('Guias locais especializados')->columnSpan(['default' => 1, 'sm' => 3]),

                                Select::make('home_hero_trust_2_icon')
                                    ->label('Confianza 2 — Ícono')
                                    ->options(\App\Support\HeroIcons::options())
                                    ->placeholder('Escudo (por defecto)')
                                    ->native(false),
                                TextInput::make('home_hero_trust_2_es')->label('Confianza 2 (ES)')->placeholder('Tours 100% seguros'),
                                TextInput::make('home_hero_trust_2_en')->label('Confianza 2 (EN)')->placeholder('100% safe tours'),
                                TextInput::make('home_hero_trust_2_pt')->label('Confianza 2 (PT)')->placeholder('Tours 100% seguros')->columnSpan(['default' => 1, 'sm' => 3]),

                                Select::make('home_hero_trust_3_icon')
                                    ->label('Confianza 3 — Ícono')
                                    ->options(\App\Support\HeroIcons::options())
                                    ->placeholder('Auriculares (por defecto)')
                                    ->native(false),
                                TextInput::make('home_hero_trust_3_es')->label('Confianza 3 (ES)')->placeholder('Atención personalizada'),
                                TextInput::make('home_hero_trust_3_en')->label('Confianza 3 (EN)')->placeholder('Personalized support'),
                                TextInput::make('home_hero_trust_3_pt')->label('Confianza 3 (PT)')->placeholder('Atendimento personalizado')->columnSpan(['default' => 1, 'sm' => 3]),

                                Select::make('home_hero_trust_4_icon')
                                    ->label('Confianza 4 — Ícono')
                                    ->options(\App\Support\HeroIcons::options())
                                    ->placeholder('Etiqueta de precio (por defecto)')
                                    ->native(false),
                                TextInput::make('home_hero_trust_4_es')->label('Confianza 4 (ES)')->placeholder('Mejor precio garantizado'),
                                TextInput::make('home_hero_trust_4_en')->label('Confianza 4 (EN)')->placeholder('Best price guaranteed'),
                                TextInput::make('home_hero_trust_4_pt')->label('Confianza 4 (PT)')->placeholder('Melhor preço garantido')->columnSpan(['default' => 1, 'sm' => 3]),
                            ]),

                        // ── Hero — texto alternativo de la foto ────────────────
                        \Filament\Forms\Components\Section::make('Hero — descripción de la foto (accesibilidad y SEO)')
                            ->description('Describe en pocas palabras QUÉ se ve en la foto del hero. Lo leen los lectores de pantalla y Google. Si cambias la foto, cambia también esta descripción.')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_hero_image_alt_es')
                                    ->label('Descripción de la foto (ES)')
                                    ->placeholder('Ciudadela inca de Machu Picchu entre montañas y nubes, Cusco, Perú')
                                    ->maxLength(180),
                                TextInput::make('home_hero_image_alt_en')
                                    ->label('Descripción de la foto (EN)')
                                    ->placeholder('Inca citadel of Machu Picchu among mountains and clouds, Cusco, Peru')
                                    ->maxLength(180),
                                TextInput::make('home_hero_image_alt_pt')
                                    ->label('Descripción de la foto (PT)')
                                    ->placeholder('Cidadela inca de Machu Picchu entre montanhas e nuvens, Cusco, Peru')
                                    ->maxLength(180),
                            ]),

                        // El pill de WhatsApp del hero se retiró el 2026-07-29 (dos
                        // CTAs del mismo canal en la primera pantalla). Su campo de
                        // texto se quitó de aquí a la vez: un campo en el panel que
                        // no cambia nada visible es peor que no tenerlo. El botón
                        // flotante de WhatsApp sigue tomando el número de
                        // Configuración → Contacto.

                        // ── Hero — botón "Ver video" ───────────────────────────
                        \Filament\Forms\Components\Section::make('Hero — botón "Ver video"')
                            ->description('Enlace del video que abre el botón "Ver video" del hero.')
                            ->collapsible()
                            ->schema([
                                TextInput::make('home_hero_video_url')
                                    ->label('URL del video')
                                    ->url()
                                    ->placeholder('https://www.youtube.com/watch?v=XXXXXXXXXXX')
                                    ->helperText('Pega el link tal como lo copias de YouTube, Vimeo o un .mp4 directo (el normal, el que sale al darle "Compartir"): un link de youtube.com/watch, youtu.be, un Short, de vimeo.com, o una URL que termine en .mp4. Si se deja VACÍO, el botón "Ver video" del hero no se muestra en el sitio.')
                                    ->columnSpanFull(),
                            ]),

                        // ── Hero — CTAs (botones) ──────────────────────────────
                        // Mockup 2026-08: el hero tiene TRES botones — rojo
                        // primario ("Reservar Ahora"), outline secundario ("Ver
                        // Tours") y el de video ("Ver Video"). Hallazgo CRO
                        // 2026-08-15: home.blade.php YA leía
                        // home_hero_cta_secondary_{es,en,pt,url} para el botón
                        // "Ver Tours" (con default en código si faltaba), pero
                        // este formulario nunca tuvo esos campos — la clienta no
                        // podía cambiar ese botón ni un poco. Se agregan aquí con
                        // la MISMA regla de URL que el primario (ruta relativa u
                        // externa, nunca la URL completa de este mismo sitio).
                        \Filament\Forms\Components\Section::make('Hero — botones (CTAs)')
                            ->description('Textos y enlace de los tres botones del hero: el rojo primario ("Reservar Ahora"), el secundario outline ("Ver Tours") y el de video ("Ver Video").')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_hero_cta_primary_es')->label('Botón primario — texto (ES)')->placeholder('Reservar Ahora'),
                                TextInput::make('home_hero_cta_primary_en')->label('Botón primario — texto (EN)')->placeholder('Book Now'),
                                TextInput::make('home_hero_cta_primary_pt')->label('Botón primario — texto (PT)')->placeholder('Reservar Agora'),
                                TextInput::make('home_hero_cta_primary_url')
                                    ->label('Botón primario — URL destino')
                                    // SIN ->url(): esa regla de Filament exige un esquema
                                    // (http/https) y rechazaría de plano la ruta relativa
                                    // ("/tours") que es justo el formato correcto para un
                                    // destino dentro de este mismo sitio. La validación
                                    // completa (formato + host propio) vive en la regla
                                    // compartida relativeOrExternalUrlRule() de abajo.
                                    ->placeholder('/tours (o https://otra-web.com si el destino es externo)')
                                    ->helperText('Opcional. Si se deja vacía, el botón lleva al listado de tours. Si el destino es una página de ESTE mismo sitio, escribe solo la ruta relativa (ej. "/tours"), NO la URL completa con https://limaamericatours.com — eso saca al visitante de staging/local hacia el sitio de producción. Una URL completa solo es correcta cuando apunta a una web externa de verdad.')
                                    ->rule(self::relativeOrExternalUrlRule())
                                    ->columnSpanFull(),

                                TextInput::make('home_hero_cta_secondary_es')->label('Botón secundario "Ver Tours" — texto (ES)')->placeholder('Ver Tours'),
                                TextInput::make('home_hero_cta_secondary_en')->label('Botón secundario "Ver Tours" — texto (EN)')->placeholder('View Tours'),
                                TextInput::make('home_hero_cta_secondary_pt')->label('Botón secundario "Ver Tours" — texto (PT)')->placeholder('Ver Tours'),
                                TextInput::make('home_hero_cta_secondary_url')
                                    ->label('Botón secundario "Ver Tours" — URL destino')
                                    ->placeholder('/tours (o https://otra-web.com si el destino es externo)')
                                    ->helperText('Opcional. Si se deja vacía, el botón lleva al listado de tours (igual que el primario). Mismas reglas que la URL del botón primario.')
                                    ->rule(self::relativeOrExternalUrlRule())
                                    ->columnSpanFull(),

                                TextInput::make('home_hero_cta_video_es')->label('Botón "Ver Video" — texto (ES)')->placeholder('Ver Video'),
                                TextInput::make('home_hero_cta_video_en')->label('Botón "Ver Video" — texto (EN)')->placeholder('Watch Video'),
                                TextInput::make('home_hero_cta_video_pt')->label('Botón "Ver Video" — texto (PT)')->placeholder('Ver Vídeo'),
                            ]),

                        // ── Barra de estadísticas del hero (mockup) ────────────
                        // Distinta de la sección "Stats (4 indicadores)" de más
                        // abajo (esa es la legacy: valor + 1 sola etiqueta sin
                        // idioma). Esta es la que lee el hero rediseñado: 4
                        // slots con ícono + etiqueta en ES/EN/PT.
                        \Filament\Forms\Components\Section::make('Barra de estadísticas del hero (nuevo diseño, 4 slots)')
                            ->description('Los 4 indicadores con ícono que se muestran en la franja de estadísticas del hero rediseñado. Distinta de la sección "Stats (4 indicadores)" más abajo, que es la del diseño anterior.')
                            ->collapsible()
                            ->schema([
                                Toggle::make('home_stats_enabled')
                                    ->label('Mostrar la barra de estadísticas en el hero')
                                    ->default(true)
                                    ->columnSpanFull(),

                                \Filament\Forms\Components\Fieldset::make('Stat 1')->columns(3)->schema([
                                    Select::make('home_stat_1_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('manual')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('home_stat_1_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Estrella (por defecto)')->native(false),
                                    TextInput::make('home_stat_1_value')
                                        ->label('Valor (manual)')
                                        ->placeholder('4.9')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('home_stat_1_source') ?: 'manual') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('home_stat_1_source') ?: 'manual') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('home_stat_1_label_es')->label('Etiqueta (ES)')->placeholder('Valoración de viajeros'),
                                    TextInput::make('home_stat_1_label_en')->label('Etiqueta (EN)')->placeholder('Traveler rating'),
                                    TextInput::make('home_stat_1_label_pt')->label('Etiqueta (PT)')->placeholder('Avaliação dos viajantes'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Stat 2')->columns(3)->schema([
                                    Select::make('home_stat_2_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('manual')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('home_stat_2_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Personas (por defecto)')->native(false),
                                    TextInput::make('home_stat_2_value')
                                        ->label('Valor (manual)')
                                        ->placeholder('50K+')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('home_stat_2_source') ?: 'manual') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('home_stat_2_source') ?: 'manual') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('home_stat_2_label_es')->label('Etiqueta (ES)')->placeholder('Viajeros felices'),
                                    TextInput::make('home_stat_2_label_en')->label('Etiqueta (EN)')->placeholder('Happy travelers'),
                                    TextInput::make('home_stat_2_label_pt')->label('Etiqueta (PT)')->placeholder('Viajantes felizes'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Stat 3')->columns(3)->schema([
                                    Select::make('home_stat_3_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('manual')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('home_stat_3_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Escudo (por defecto)')->native(false),
                                    TextInput::make('home_stat_3_value')
                                        ->label('Valor (manual)')
                                        ->placeholder('100%')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('home_stat_3_source') ?: 'manual') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('home_stat_3_source') ?: 'manual') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('home_stat_3_label_es')->label('Etiqueta (ES)')->placeholder('Cancelación gratuita'),
                                    TextInput::make('home_stat_3_label_en')->label('Etiqueta (EN)')->placeholder('Free cancellation'),
                                    TextInput::make('home_stat_3_label_pt')->label('Etiqueta (PT)')->placeholder('Cancelamento gratuito'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Stat 4')->columns(3)->schema([
                                    Select::make('home_stat_4_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('manual')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('home_stat_4_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Medalla (por defecto)')->native(false),
                                    TextInput::make('home_stat_4_value')
                                        ->label('Valor (manual)')
                                        ->placeholder('10+')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('home_stat_4_source') ?: 'manual') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('home_stat_4_source') ?: 'manual') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('home_stat_4_label_es')->label('Etiqueta (ES)')->placeholder('Años de experiencia'),
                                    TextInput::make('home_stat_4_label_en')->label('Etiqueta (EN)')->placeholder('Years of experience'),
                                    TextInput::make('home_stat_4_label_pt')->label('Etiqueta (PT)')->placeholder('Anos de experiência'),
                                ]),
                                TextInput::make('company_started_year')
                                    ->label('Año de inicio de operaciones')
                                    ->numeric()
                                    ->placeholder('Ej: 2016')
                                    ->helperText('Usado por la fuente "Años de operación" de arriba: calcula los años activos como (año actual − este año). Vacío = ese slot se oculta. No pongas un año que no puedas confirmar con el cliente.')
                                    ->columnSpanFull(),

                                \Filament\Forms\Components\Fieldset::make('Reseñas externas verificadas (Google/Tripadvisor)')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('reviews_external_rating')
                                            ->label('Rating externo')
                                            ->numeric()
                                            ->step(0.1)
                                            ->placeholder('5.0')
                                            ->helperText('Ej: el snapshot público de Google+Tripadvisor de la ficha del negocio.'),
                                        TextInput::make('reviews_external_count')
                                            ->label('Cantidad de opiniones')
                                            ->numeric()
                                            ->placeholder('255'),
                                        TextInput::make('reviews_external_url')
                                            ->label('Enlace a la ficha real')
                                            ->url()
                                            ->placeholder('https://www.google.com/maps/place/...')
                                            ->helperText('Obligatorio en la práctica: sin enlace verificable, el número es indistinguible de uno inventado.')
                                            ->columnSpanFull(),
                                        \Filament\Forms\Components\Placeholder::make('reviews_external_note')
                                            ->label('')
                                            ->content('Alimenta la fuente "Verificado: rating externo" de arriba. Se carga a mano mientras el sitio no tenga una integración propia con las APIs de reseñas.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ── Stats legados: RETIRADOS 2026-08-01 ───────────────
                        // Existía aquí una sección "Stats (4 indicadores)" con
                        // las claves stats_rating / stats_years / stats_tours y
                        // home_stat_{rating,travelers,years,tours}_label. Ninguna
                        // vista las leía (grep en resources/ y app/: 0 usos), así
                        // que la editora rellenaba campos que no salían en ningún
                        // lado, justo al lado de la sección "Barra de estadísticas
                        // del hero" que sí funciona — hallazgo del CRO en la
                        // validación de este lote. Los valores que hubiera en la
                        // tabla `settings` quedan huérfanos, pero inertes: nadie
                        // los lee. Si algún día se necesitan, la barra viva usa
                        // home_stat_{1..4}_value/_label_{locale}/_icon.

                        // ── Sección "Más Comprados" ───────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Más Comprados"')
                            ->collapsible()
                            ->columns(2)
                            ->schema([
                                TextInput::make('home_sec_featured_eyebrow_es')->label('Eyebrow (ES)')->placeholder('NUESTROS TOURS'),
                                TextInput::make('home_sec_featured_eyebrow_en')->label('Eyebrow (EN)')->placeholder('OUR TOURS'),
                                TextInput::make('home_sec_featured_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('NOSSOS TOURS'),
                                TextInput::make('home_sec_featured_title_es')->label('Título (ES)')->placeholder('Más Comprados'),
                                TextInput::make('home_sec_featured_title_en')->label('Título (EN)')->placeholder('Best Sellers'),
                                TextInput::make('home_sec_featured_title_pt')->label('Título (PT)')->placeholder('Mais Vendidos'),
                            ]),

                        // ── Sección "Tours en Lima, Ica y Cusco" ─────────────
                        \Filament\Forms\Components\Section::make('Sección "Tours en Lima, Ica y Cusco"')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_cities_title_es')->label('Título (ES)')->placeholder('Tours en Lima, Ica y Cusco'),
                                TextInput::make('home_sec_cities_title_en')->label('Título (EN)')->placeholder('Tours in Lima, Ica and Cusco'),
                                TextInput::make('home_sec_cities_title_pt')->label('Título (PT)')->placeholder('Tours em Lima, Ica e Cusco'),
                            ]),

                        // ── Página Gracias ────────────────────────────────────
                        \Filament\Forms\Components\Section::make('Página "Gracias" (post-contacto)')
                            ->collapsible()
                            ->schema([
                                FileUpload::make('gracias_banner_image')
                                    ->label('Imagen de fondo (panel derecho)')
                                    ->image()
                                    ->disk('media')
                                    ->directory('home')
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1920, disk: 'media', deletePrevious: true))
                                    ->helperText('Dejar vacío para usar Rectangle 19218.jpg por defecto. Se optimiza a WebP (máx. 1920px).')
                                    ->columnSpanFull(),
                                FileUpload::make('gracias_logo_image')
                                    ->label('Logo sobre la imagen')
                                    ->image()
                                    ->disk('media')
                                    ->directory('home')
                                    ->helperText('Dejar vacío para usar logo.png por defecto. (El logo NO se convierte para conservar la transparencia original.)')
                                    ->columnSpanFull(),
                                TextInput::make('gracias_badge_es')->label('Badge/eyebrow (ES)')->placeholder('Tu mensaje fue enviado'),
                                TextInput::make('gracias_badge_en')->label('Badge/eyebrow (EN)')->placeholder('Your message was sent'),
                                TextInput::make('gracias_badge_pt')->label('Badge/eyebrow (PT)')->placeholder('Sua mensagem foi enviada'),
                                Textarea::make('gracias_title_es')->rows(2)->label('Título (ES)')->placeholder("Gracias por\ncontactarnos"),
                                Textarea::make('gracias_title_en')->rows(2)->label('Título (EN)')->placeholder("Thanks for\ncontacting us"),
                                Textarea::make('gracias_title_pt')->rows(2)->label('Título (PT)')->placeholder("Obrigado por\nnos contatar"),
                                Textarea::make('gracias_body_es')->rows(3)->label('Cuerpo (ES)')->placeholder('Hemos captado tus datos de forma segura, pronto nos pondremos en contacto contigo.')->columnSpanFull(),
                                Textarea::make('gracias_body_en')->rows(3)->label('Cuerpo (EN)')->placeholder('We have securely captured your information and will be in touch with you shortly.')->columnSpanFull(),
                                Textarea::make('gracias_body_pt')->rows(3)->label('Cuerpo (PT)')->placeholder('Capturamos seus dados com segurança e entraremos em contato em breve.')->columnSpanFull(),
                                TextInput::make('gracias_cta_es')->label('Botón CTA (ES)')->placeholder('Volver a inicio'),
                                TextInput::make('gracias_cta_en')->label('Botón CTA (EN)')->placeholder('Back to home'),
                                TextInput::make('gracias_cta_pt')->label('Botón CTA (PT)')->placeholder('Voltar ao início'),
                            ]),

                        // ── Sección 3: Más Visitados ────────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Más Visitados" (Destinos)')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_destinos_eyebrow_es')->label('Eyebrow (ES)')->placeholder('MÁS VISITADOS'),
                                TextInput::make('home_sec_destinos_eyebrow_en')->label('Eyebrow (EN)')->placeholder('MOST VISITED'),
                                TextInput::make('home_sec_destinos_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('MAIS VISITADOS'),
                                TextInput::make('home_sec_destinos_title_es')->label('Título (ES)')->placeholder('Descubre las Ciudades<br>más Visitadas del Perú')->columnSpanFull(),
                                TextInput::make('home_sec_destinos_title_en')->label('Título (EN)')->placeholder('Discover the Most Visited Cities of Peru')->columnSpanFull(),
                                TextInput::make('home_sec_destinos_title_pt')->label('Título (PT)')->placeholder('Descubra as Cidades mais Visitadas do Peru')->columnSpanFull(),
                                TextInput::make('home_destinos_footer_es')->label('Cierre sección (ES)')->placeholder('Experiencias auténticas, memorias inolvidables. Viaja con')->columnSpanFull(),
                                TextInput::make('home_destinos_footer_en')->label('Cierre sección (EN)')->placeholder('Authentic experiences, unforgettable memories. Travel with')->columnSpanFull(),
                                TextInput::make('home_destinos_footer_pt')->label('Cierre sección (PT)')->placeholder('Experiências autênticas, memórias inesquecíveis. Viaje com')->columnSpanFull(),
                            ]),

                        // ── Repeater: Destinos ──────────────────────────────────────
                        \Filament\Forms\Components\Section::make('Destinos (cards con imagen)')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_destinos')
                                    ->label('Destinos')
                                    ->helperText('Dejar vacío para usar los destinos por defecto (Cusco, Lima, Ica).')
                                    ->schema([
                                        TextInput::make('title_es')->label('Título (ES)')->required(),
                                        TextInput::make('title_en')->label('Título (EN)'),
                                        TextInput::make('title_pt')->label('Título (PT)'),
                                        TextInput::make('badge_es')->label('Badge (ES)'),
                                        TextInput::make('badge_en')->label('Badge (EN)'),
                                        TextInput::make('badge_pt')->label('Badge (PT)'),
                                        Textarea::make('desc_es')->label('Descripción (ES)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_en')->label('Descripción (EN)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_pt')->label('Descripción (PT)')->rows(2)->columnSpanFull(),
                                        \Filament\Forms\Components\Placeholder::make('img_note')
                                            ->label('Imagen')
                                            ->content('La imagen de cada tarjeta se sube en la sección "Imágenes de las tarjetas del Home" (más abajo), por posición.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title_es'] ?? null)
                                    ->addActionLabel('+ Agregar destino')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 4: Por qué elegirnos ─────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "¿Por qué elegirnos?"')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_why_eyebrow_es')->label('Eyebrow (ES)')->placeholder('VIAJA CON CONFIANZA'),
                                TextInput::make('home_sec_why_eyebrow_en')->label('Eyebrow (EN)')->placeholder('TRAVEL WITH CONFIDENCE'),
                                TextInput::make('home_sec_why_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('VIAJE COM CONFIANÇA'),
                                TextInput::make('home_sec_why_title_es')->label('Título (ES)')->placeholder('¿Por qué elegir<br>Lima América Tours?')->columnSpanFull(),
                                TextInput::make('home_sec_why_title_en')->label('Título (EN)')->placeholder('Why choose<br>Lima América Tours?')->columnSpanFull(),
                                TextInput::make('home_sec_why_title_pt')->label('Título (PT)')->placeholder('Por que escolher<br>Lima América Tours?')->columnSpanFull(),
                                TextInput::make('home_sec_why_subtitle_es')->label('Subtítulo (ES)')->placeholder('Más que un tour, te ofrecemos<br>experiencias inolvidables.')->columnSpanFull(),
                                TextInput::make('home_sec_why_subtitle_en')->label('Subtítulo (EN)')->placeholder('More than a tour, we offer you<br>unforgettable experiences.')->columnSpanFull(),
                                TextInput::make('home_sec_why_subtitle_pt')->label('Subtítulo (PT)')->placeholder('Mais que um tour, oferecemos<br>experiências inesquecíveis.')->columnSpanFull(),
                                TextInput::make('home_trust_banner_es')->label('Banner confianza (ES)')->placeholder('Reserva fácil, segura y<br><strong class="font-bold text-orange-500">100% garantizada</strong>')->columnSpanFull(),
                                TextInput::make('home_trust_banner_en')->label('Banner confianza (EN)')->placeholder('Easy, secure and<br><strong class="font-bold text-orange-500">100% guaranteed</strong> booking')->columnSpanFull(),
                                TextInput::make('home_trust_banner_pt')->label('Banner confianza (PT)')->placeholder('Reserva fácil, segura e<br><strong class="font-bold text-orange-500">100% garantida</strong>')->columnSpanFull(),
                            ]),

                        // ── Repeater: "Viaja con confianza y vive la mejor experiencia"
                        // (sección real del home rediseñado, .lat-why). Antes este
                        // repeater existía en el panel pero home.blade.php pintaba un
                        // array de 5 razones fijo en un @php, sin leer nunca este campo
                        // (hallazgo 2026-08-11): la clienta podía editar "razones" en
                        // Configuración → Home y no pasaba NADA en el sitio. Ahora sí
                        // alimenta esa sección; el ícono de cada tarjeta se asigna por
                        // posición (mismo criterio que blocks.stats en Nosotros), ya
                        // que el repeater no tiene campo de ícono.
                        \Filament\Forms\Components\Section::make('Razones "Viaja con confianza y vive la mejor experiencia"')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_why_items')
                                    ->label('Razones')
                                    ->helperText('Dejar vacío para usar las 5 razones por defecto (Guías certificados, Viajes seguros, Mejor precio garantizado, Atención personalizada, Cancelación flexible). Reordenable: arrastra para cambiar el orden en pantalla.')
                                    ->schema([
                                        TextInput::make('title_es')->label('Título (ES)')->required(),
                                        TextInput::make('title_en')->label('Título (EN)'),
                                        TextInput::make('title_pt')->label('Título (PT)'),
                                        Textarea::make('desc_es')->label('Descripción (ES)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_en')->label('Descripción (EN)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_pt')->label('Descripción (PT)')->rows(2)->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title_es'] ?? null)
                                    ->addActionLabel('+ Agregar razón')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 5: ¿Qué tipo de tour? ────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "¿Qué tipo de tour estás buscando?"')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_types_eyebrow_es')->label('Eyebrow (ES)')->placeholder('CATEGORÍAS'),
                                TextInput::make('home_sec_types_eyebrow_en')->label('Eyebrow (EN)')->placeholder('CATEGORIES'),
                                TextInput::make('home_sec_types_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('CATEGORIAS'),
                                TextInput::make('home_sec_types_title_es')->label('Título (ES)')->placeholder('¿Qué tipo de tour estás buscando?')->columnSpanFull(),
                                TextInput::make('home_sec_types_title_en')->label('Título (EN)')->placeholder('What type of tour are you looking for?')->columnSpanFull(),
                                TextInput::make('home_sec_types_title_pt')->label('Título (PT)')->placeholder('Que tipo de tour você está procurando?')->columnSpanFull(),
                            ]),

                        // ── Repeater: Tour Type Tabs ─────────────────────────────────
                        \Filament\Forms\Components\Section::make('Tabs de tipo de tour')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_tour_type_tabs')
                                    ->label('Tipos de tour')
                                    ->helperText('Dejar vacío para usar los 4 tipos por defecto. El campo "id" debe ser único (cult, adv, cul, oth).')
                                    ->schema([
                                        TextInput::make('id')->label('ID (único, sin espacios)')->placeholder('cult')->required(),
                                        TextInput::make('label_es')->label('Label tab (ES)')->required(),
                                        TextInput::make('label_en')->label('Label tab (EN)'),
                                        TextInput::make('label_pt')->label('Label tab (PT)'),
                                        TextInput::make('title_es')->label('Título panel (ES)'),
                                        TextInput::make('title_en')->label('Título panel (EN)'),
                                        TextInput::make('title_pt')->label('Título panel (PT)'),
                                        \Filament\Forms\Components\Placeholder::make('img_note')
                                            ->label('Imagen')
                                            ->content('La imagen se sube en la sección "Imágenes de las tarjetas del Home" (más abajo), por posición.')
                                            ->columnSpanFull(),
                                        TextInput::make('eyebrow_es')->label('Eyebrow (ES)'),
                                        TextInput::make('eyebrow_en')->label('Eyebrow (EN)'),
                                        TextInput::make('eyebrow_pt')->label('Eyebrow (PT)'),
                                        Textarea::make('desc_es')->label('Descripción (ES)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_en')->label('Descripción (EN)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_pt')->label('Descripción (PT)')->rows(2)->columnSpanFull(),
                                        TextInput::make('price')->label('Precio desde ('.trim(\App\Support\Money::prefix(\App\Support\Money::site())).')')->numeric(),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => ($state['label_es'] ?? null))
                                    ->addActionLabel('+ Agregar tipo')
                                    ->columnSpanFull(),
                            ]),

                        // ── Repeater: Footer features ────────────────────────────────
                        \Filament\Forms\Components\Section::make('Features del footer de sección "¿Qué tipo de tour?"')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_footer_features')
                                    ->label('Features (4 íconos en fila)')
                                    ->helperText('Dejar vacío para usar los 4 features por defecto.')
                                    ->schema([
                                        TextInput::make('label_es')->label('Etiqueta (ES)')->required(),
                                        TextInput::make('label_en')->label('Etiqueta (EN)'),
                                        TextInput::make('label_pt')->label('Etiqueta (PT)'),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label_es'] ?? null)
                                    ->addActionLabel('+ Agregar feature')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 6: Experiencias ───────────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Descubre experiencias únicas"')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_exp_eyebrow_es')->label('Eyebrow (ES)')->placeholder('EXPERIENCIAS'),
                                TextInput::make('home_sec_exp_eyebrow_en')->label('Eyebrow (EN)')->placeholder('EXPERIENCES'),
                                TextInput::make('home_sec_exp_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('EXPERIÊNCIAS'),
                                TextInput::make('home_sec_exp_title_es')->label('Título (ES)')->placeholder('Descubre experiencias únicas')->columnSpanFull(),
                                TextInput::make('home_sec_exp_title_en')->label('Título (EN)')->placeholder('Discover unique experiences')->columnSpanFull(),
                                TextInput::make('home_sec_exp_title_pt')->label('Título (PT)')->placeholder('Descubra experiências únicas')->columnSpanFull(),
                                TextInput::make('home_swipe_hint_es')->label('Hint deslizar (ES)')->placeholder('Desliza para ver más tours')->columnSpanFull(),
                                TextInput::make('home_swipe_hint_en')->label('Hint deslizar (EN)')->placeholder('Swipe to see more tours')->columnSpanFull(),
                                TextInput::make('home_swipe_hint_pt')->label('Hint deslizar (PT)')->placeholder('Deslize para ver mais tours')->columnSpanFull(),
                                TextInput::make('home_swipe_sub_es')->label('Subtexto hint (ES)')->placeholder('Descubre nuestras mejores experiencias')->columnSpanFull(),
                                TextInput::make('home_swipe_sub_en')->label('Subtexto hint (EN)')->placeholder('Discover our best experiences')->columnSpanFull(),
                                TextInput::make('home_swipe_sub_pt')->label('Subtexto hint (PT)')->placeholder('Descubra nossas melhores experiências')->columnSpanFull(),
                            ]),

                        // ── Repeater: Experiencias únicas ────────────────────────────
                        \Filament\Forms\Components\Section::make('Cards de "Experiencias únicas"')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_exp_tours')
                                    ->label('Experiencias')
                                    ->helperText('Dejar vacío para usar las 4 tarjetas por defecto.')
                                    ->schema([
                                        TextInput::make('title_es')->label('Título (ES)')->required(),
                                        TextInput::make('title_en')->label('Título (EN)'),
                                        TextInput::make('title_pt')->label('Título (PT)'),
                                        TextInput::make('badge_es')->label('Badge (ES)'),
                                        TextInput::make('badge_en')->label('Badge (EN)'),
                                        TextInput::make('badge_pt')->label('Badge (PT)'),
                                        \Filament\Forms\Components\Placeholder::make('img_note')
                                            ->label('Imagen')
                                            ->content('La imagen se sube en la sección "Imágenes de las tarjetas del Home" (más abajo), por posición.')
                                            ->columnSpanFull(),
                                        TextInput::make('badgeBg')->label('Color badge (teal-800 / orange-500)')->placeholder('teal-800'),
                                        TextInput::make('slug')->label('Slug del tour'),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title_es'] ?? null)
                                    ->addActionLabel('+ Agregar experiencia')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 6B: Opiniones ─────────────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Opiniones de viajeros"')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_reviews_eyebrow_es')->label('Eyebrow (ES)')->placeholder('OPINIONES REALES'),
                                TextInput::make('home_sec_reviews_eyebrow_en')->label('Eyebrow (EN)')->placeholder('REAL REVIEWS'),
                                TextInput::make('home_sec_reviews_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('OPINIÕES REAIS'),
                                TextInput::make('home_sec_reviews_title_es')->label('Título (ES)')->placeholder('Lo que dicen nuestros viajeros')->columnSpanFull(),
                                TextInput::make('home_sec_reviews_title_en')->label('Título (EN)')->placeholder('What our travelers say')->columnSpanFull(),
                                TextInput::make('home_sec_reviews_title_pt')->label('Título (PT)')->placeholder('O que dizem nossos viajantes')->columnSpanFull(),
                                TextInput::make('home_sec_reviews_subtitle_es')->label('Subtítulo (ES)')->placeholder('Miles de viajeros han vivido el Perú con nosotros.')->columnSpanFull(),
                                TextInput::make('home_sec_reviews_subtitle_en')->label('Subtítulo (EN)')->placeholder('Thousands of travelers have experienced Peru with us.')->columnSpanFull(),
                                TextInput::make('home_sec_reviews_subtitle_pt')->label('Subtítulo (PT)')->placeholder('Milhares de viajantes viveram o Peru conosco.')->columnSpanFull(),
                            ]),

                        // ── Sección 7: FAQs ──────────────────────────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Preguntas frecuentes" (home)')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_faq_title_es')->label('Título (ES)')->placeholder('Preguntas frecuentes')->columnSpanFull(),
                                TextInput::make('home_sec_faq_title_en')->label('Título (EN)')->placeholder('Frequently asked questions')->columnSpanFull(),
                                TextInput::make('home_sec_faq_title_pt')->label('Título (PT)')->placeholder('Perguntas frequentes')->columnSpanFull(),
                                TextInput::make('home_sec_faq_subtitle_es')->label('Subtítulo (ES)')->placeholder('Resolvemos las dudas más comunes...')->columnSpanFull(),
                                TextInput::make('home_sec_faq_subtitle_en')->label('Subtítulo (EN)')->placeholder('We resolve the most common doubts...')->columnSpanFull(),
                                TextInput::make('home_sec_faq_subtitle_pt')->label('Subtítulo (PT)')->placeholder('Resolvemos as dúvidas mais comuns...')->columnSpanFull(),
                            ]),

                        // ── Repeater: FAQs home ──────────────────────────────────────
                        \Filament\Forms\Components\Section::make('FAQs del acordeón (home) — separadas del AEO')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_faqs')
                                    ->label('Preguntas frecuentes (home)')
                                    ->helperText('Estas FAQs son para el acordeón visual de la home. Son DISTINTAS a las FAQs del tab AEO/FAQ (schema estructurado). Dejar vacío para usar las 6 preguntas por defecto.')
                                    ->schema([
                                        TextInput::make('q_es')->label('Pregunta (ES)')->required()->columnSpanFull(),
                                        TextInput::make('q_en')->label('Pregunta (EN)')->columnSpanFull(),
                                        TextInput::make('q_pt')->label('Pregunta (PT)')->columnSpanFull(),
                                        Textarea::make('a_es')->label('Respuesta (ES)')->rows(2)->required()->columnSpanFull(),
                                        Textarea::make('a_en')->label('Respuesta (EN)')->rows(2)->columnSpanFull(),
                                        Textarea::make('a_pt')->label('Respuesta (PT)')->rows(2)->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['q_es'] ?? null)
                                    ->addActionLabel('+ Agregar pregunta')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 8: Verificado y Recomendado ──────────────────────
                        \Filament\Forms\Components\Section::make('Sección "Verificado y Recomendado"')
                            ->collapsible()->collapsed()
                            ->columns(3)
                            ->schema([
                                TextInput::make('home_sec_reco_eyebrow_es')->label('Eyebrow pill (ES)')->placeholder('VERIFICADO Y RECOMENDADO POR')->columnSpanFull(),
                                TextInput::make('home_sec_reco_eyebrow_en')->label('Eyebrow pill (EN)')->placeholder('VERIFIED AND RECOMMENDED BY')->columnSpanFull(),
                                TextInput::make('home_sec_reco_eyebrow_pt')->label('Eyebrow pill (PT)')->placeholder('VERIFICADO E RECOMENDADO POR')->columnSpanFull(),
                                TextInput::make('home_reco_intro_es')->label('Intro "Estamos recomendados por" (ES)')->columnSpanFull(),
                                TextInput::make('home_reco_intro_en')->label('Intro "Estamos recomendados por" (EN)')->columnSpanFull(),
                                TextInput::make('home_reco_intro_pt')->label('Intro "Estamos recomendados por" (PT)')->columnSpanFull(),
                                TextInput::make('home_reco_headline_es')->label('Headline (ES)')->placeholder('Best Tours in Lima')->columnSpanFull(),
                                TextInput::make('home_reco_headline_en')->label('Headline (EN)')->placeholder('Best Tours in Lima')->columnSpanFull(),
                                TextInput::make('home_reco_headline_pt')->label('Headline (PT)')->placeholder('Best Tours in Lima')->columnSpanFull(),
                                Textarea::make('home_reco_body_es')->rows(2)->label('Cuerpo (ES)')->placeholder('Una de las mejores empresas...')->columnSpanFull(),
                                Textarea::make('home_reco_body_en')->rows(2)->label('Cuerpo (EN)')->columnSpanFull(),
                                Textarea::make('home_reco_body_pt')->rows(2)->label('Cuerpo (PT)')->columnSpanFull(),
                                TextInput::make('home_reco_award_title_es')->label('Premio título (ES)')->placeholder('GANAMOS EL PREMIO<br>DE VERIFICACIÓN')->columnSpanFull(),
                                TextInput::make('home_reco_award_title_en')->label('Premio título (EN)')->columnSpanFull(),
                                TextInput::make('home_reco_award_title_pt')->label('Premio título (PT)')->columnSpanFull(),
                                Textarea::make('home_reco_award_desc_es')->rows(2)->label('Premio descripción (ES)')->placeholder('para los mejores tours...')->columnSpanFull(),
                                Textarea::make('home_reco_award_desc_en')->rows(2)->label('Premio descripción (EN)')->columnSpanFull(),
                                Textarea::make('home_reco_award_desc_pt')->rows(2)->label('Premio descripción (PT)')->columnSpanFull(),
                                TextInput::make('home_sec_why_reco_title_es')->label('H3 "¿Por qué somos recomendados?" (ES)')->placeholder('¿Por qué somos recomendados?')->columnSpanFull(),
                                TextInput::make('home_sec_why_reco_title_en')->label('H3 "¿Por qué somos recomendados?" (EN)')->columnSpanFull(),
                                TextInput::make('home_sec_why_reco_title_pt')->label('H3 "¿Por qué somos recomendados?" (PT)')->columnSpanFull(),
                                TextInput::make('home_sec_verified_in_es')->label('H3 "Recomendado y verificado en" (ES)')->placeholder('Recomendado y verificado en')->columnSpanFull(),
                                TextInput::make('home_sec_verified_in_en')->label('H3 "Recomendado y verificado en" (EN)')->columnSpanFull(),
                                TextInput::make('home_sec_verified_in_pt')->label('H3 "Recomendado y verificado en" (PT)')->columnSpanFull(),
                                Textarea::make('home_reviews_footer_es')->rows(2)->label('Footer reseñas (ES)')->placeholder('Miles de reseñas verificadas...')->columnSpanFull(),
                                Textarea::make('home_reviews_footer_en')->rows(2)->label('Footer reseñas (EN)')->columnSpanFull(),
                                Textarea::make('home_reviews_footer_pt')->rows(2)->label('Footer reseñas (PT)')->columnSpanFull(),
                            ]),

                        // ── Repeater: ¿Por qué somos recomendados? ──────────────────
                        \Filament\Forms\Components\Section::make('Cards "¿Por qué somos recomendados?"')
                            ->collapsible()->collapsed()
                            ->schema([
                                Repeater::make('home_reco_items')
                                    ->label('Razones de recomendación')
                                    ->helperText('Dejar vacío para usar las 4 razones por defecto.')
                                    ->schema([
                                        TextInput::make('title_es')->label('Título (ES)')->required(),
                                        TextInput::make('title_en')->label('Título (EN)'),
                                        TextInput::make('title_pt')->label('Título (PT)'),
                                        Textarea::make('desc_es')->label('Descripción (ES)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_en')->label('Descripción (EN)')->rows(2)->columnSpanFull(),
                                        Textarea::make('desc_pt')->label('Descripción (PT)')->rows(2)->columnSpanFull(),
                                        Select::make('icon')
                                            ->label('Icono')
                                            ->options(['shield' => 'Escudo (shield)', 'star' => 'Estrella (star)', 'headset' => 'Auriculares (headset)', 'medal' => 'Medalla (medal)'])
                                            ->native(false),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title_es'] ?? null)
                                    ->addActionLabel('+ Agregar razón')
                                    ->columnSpanFull(),
                            ]),

                        // ── Sección 9: Newsletter (bloque oscuro con foto) ───────────
                        // Mockup 2026-08: sección oscura de suscripción con foto de
                        // fondo, texto y 4 beneficios con ícono. Mismo patrón que el
                        // resto del Home: toggle para mostrar/ocultar, imagen en el
                        // disco "media" (igual que home_hero_image), y textos ES/EN/PT
                        // con default en el blade si se dejan vacíos.
                        \Filament\Forms\Components\Section::make('Newsletter (bloque oscuro con foto)')
                            ->collapsible()->collapsed()
                            ->schema([
                                Toggle::make('home_news_enabled')
                                    ->label('Mostrar el bloque de newsletter en el home')
                                    ->default(true)
                                    ->columnSpanFull(),
                                FileUpload::make('home_news_image')
                                    ->label('Imagen de fondo')
                                    ->image()->disk('media')->directory('home')
                                    ->saveUploadedFileUsing(\App\Support\ImageOptimizer::saver('home', 1920, disk: 'media', deletePrevious: true))
                                    ->helperText('Opcional: el blade tiene una imagen por defecto si se deja vacía. Se optimiza a WebP (máx. 1920px).')
                                    ->columnSpanFull(),
                                TextInput::make('home_news_eyebrow_es')->label('Eyebrow (ES)')->placeholder('Viaja. Explora. Vive.'),
                                TextInput::make('home_news_eyebrow_en')->label('Eyebrow (EN)')->placeholder('Travel. Explore. Live.'),
                                TextInput::make('home_news_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('Viaje. Explore. Viva.'),
                                TextInput::make('home_news_title_es')->label('Título (ES)')->placeholder('Tu próxima aventura empieza aquí')->columnSpanFull(),
                                TextInput::make('home_news_title_en')->label('Título (EN)')->placeholder('Your next adventure starts here')->columnSpanFull(),
                                TextInput::make('home_news_title_pt')->label('Título (PT)')->placeholder('Sua próxima aventura começa aqui')->columnSpanFull(),
                                Textarea::make('home_news_sub_es')->rows(2)->label('Subtítulo (ES)')->placeholder('Suscríbete a nuestro boletín para recibir noticias, ofertas y promociones especiales.')->columnSpanFull(),
                                Textarea::make('home_news_sub_en')->rows(2)->label('Subtítulo (EN)')->placeholder('Subscribe to our newsletter to receive news, offers and special promotions.')->columnSpanFull(),
                                Textarea::make('home_news_sub_pt')->rows(2)->label('Subtítulo (PT)')->placeholder('Assine nossa newsletter para receber notícias, ofertas e promoções especiais.')->columnSpanFull(),

                                \Filament\Forms\Components\Fieldset::make('Beneficio 1')->columns(3)->schema([
                                    Select::make('home_news_benefit_1_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Etiqueta (por defecto)')->native(false),
                                    TextInput::make('home_news_benefit_1_title_es')->label('Título (ES)')->placeholder('Ofertas exclusivas'),
                                    TextInput::make('home_news_benefit_1_title_en')->label('Título (EN)')->placeholder('Exclusive offers'),
                                    TextInput::make('home_news_benefit_1_title_pt')->label('Título (PT)')->placeholder('Ofertas exclusivas'),
                                    Textarea::make('home_news_benefit_1_text_es')->rows(2)->label('Texto (ES)')->placeholder('Accede a descuentos y promociones especiales.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_1_text_en')->rows(2)->label('Texto (EN)')->placeholder('Get access to exclusive discounts and special promotions.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_1_text_pt')->rows(2)->label('Texto (PT)')->placeholder('Acesse descontos e promoções especiais.')->columnSpanFull(),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Beneficio 2')->columns(3)->schema([
                                    Select::make('home_news_benefit_2_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Mapa (por defecto)')->native(false),
                                    TextInput::make('home_news_benefit_2_title_es')->label('Título (ES)')->placeholder('Novedades de viaje'),
                                    TextInput::make('home_news_benefit_2_title_en')->label('Título (EN)')->placeholder('Travel updates'),
                                    TextInput::make('home_news_benefit_2_title_pt')->label('Título (PT)')->placeholder('Novidades de viagem'),
                                    Textarea::make('home_news_benefit_2_text_es')->rows(2)->label('Texto (ES)')->placeholder('Recibe inspiración y nuevas experiencias cada semana.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_2_text_en')->rows(2)->label('Texto (EN)')->placeholder('Get inspiration and new experiences every week.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_2_text_pt')->rows(2)->label('Texto (PT)')->placeholder('Receba inspiração e novas experiências toda semana.')->columnSpanFull(),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Beneficio 3')->columns(3)->schema([
                                    Select::make('home_news_benefit_3_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Reloj (por defecto)')->native(false),
                                    TextInput::make('home_news_benefit_3_title_es')->label('Título (ES)')->placeholder('Eventos especiales'),
                                    TextInput::make('home_news_benefit_3_title_en')->label('Título (EN)')->placeholder('Special events'),
                                    TextInput::make('home_news_benefit_3_title_pt')->label('Título (PT)')->placeholder('Eventos especiais'),
                                    Textarea::make('home_news_benefit_3_text_es')->rows(2)->label('Texto (ES)')->placeholder('Sé el primero en enterarte de nuestros eventos y lanzamientos.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_3_text_en')->rows(2)->label('Texto (EN)')->placeholder('Be the first to know about our events and launches.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_3_text_pt')->rows(2)->label('Texto (PT)')->placeholder('Seja o primeiro a saber sobre nossos eventos e lançamentos.')->columnSpanFull(),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Beneficio 4')->columns(3)->schema([
                                    Select::make('home_news_benefit_4_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Auriculares (por defecto)')->native(false),
                                    TextInput::make('home_news_benefit_4_title_es')->label('Título (ES)')->placeholder('Atención preferente'),
                                    TextInput::make('home_news_benefit_4_title_en')->label('Título (EN)')->placeholder('Priority support'),
                                    TextInput::make('home_news_benefit_4_title_pt')->label('Título (PT)')->placeholder('Atendimento preferencial'),
                                    Textarea::make('home_news_benefit_4_text_es')->rows(2)->label('Texto (ES)')->placeholder('Soporte prioritario para suscriptores en todo momento.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_4_text_en')->rows(2)->label('Texto (EN)')->placeholder('Priority support for subscribers at all times.')->columnSpanFull(),
                                    Textarea::make('home_news_benefit_4_text_pt')->rows(2)->label('Texto (PT)')->placeholder('Suporte prioritário para assinantes a qualquer momento.')->columnSpanFull(),
                                ]),
                            ]),
                    ]),
                    Tabs\Tab::make('Nosotros')->icon('heroicon-o-users')->schema([
                        // ── Banda "Miles de viajeros ya confiaron en nosotros" ──
                        // Mismo patrón que "Barra de estadísticas del hero" (pestaña
                        // Home): fuente elegible por slot + ícono + etiqueta por
                        // idioma. Corrige el bug de las 4 tarjetas en "0" (el valor
                        // ahora se resuelve con HomeStatsResolver::resolveAboutBand,
                        // no con los defaults inventados que tenía antes el Blade).
                        \Filament\Forms\Components\Section::make('Franja de confianza (banda oscura, 5 indicadores)')
                            ->description('Los 5 indicadores de la franja oscura "Miles de viajeros ya confiaron en nosotros" y de la barra compacta encimada al hero de /nosotros (misma resolución, dos vistas). El 5to (Valores) siempre muestra el conteo real de los tags de Misión/Visión/Valores de esta misma página — solo su etiqueta e ícono son editables aquí. Fix 2026-08-12: antes solo 2 de 4 traían dato (Tours y Valores); ahora los slots 1, 2 y 4 arrancan en fuentes reales (rating/reseñas/destinos) en vez de fuentes vacías. Cada tarjeta se oculta sola si su fuente no tiene dato — nunca un número inventado.')
                            ->collapsible()
                            ->schema([
                                Toggle::make('about_stats_enabled')
                                    ->label('Mostrar la franja de confianza en Nosotros')
                                    ->default(true)
                                    ->columnSpanFull(),

                                \Filament\Forms\Components\Fieldset::make('Indicador 1 — Valoración de viajeros')->columns(3)->schema([
                                    Select::make('about_stat_1_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('rating_real')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('about_stat_1_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Estrella (por defecto)')->native(false),
                                    TextInput::make('about_stat_1_value')
                                        ->label('Valor (manual)')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('about_stat_1_source') ?: 'rating_real') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('about_stat_1_source') ?: 'rating_real') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('about_stat_1_label_es')->label('Etiqueta (ES)')->placeholder('Valoración de viajeros'),
                                    TextInput::make('about_stat_1_label_en')->label('Etiqueta (EN)')->placeholder('Traveler rating'),
                                    TextInput::make('about_stat_1_label_pt')->label('Etiqueta (PT)')->placeholder('Avaliação dos viajantes'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Indicador 2 — Opiniones de viajeros')->columns(3)->schema([
                                    Select::make('about_stat_2_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('reviews_count')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('about_stat_2_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Grupo (por defecto)')->native(false),
                                    TextInput::make('about_stat_2_value')
                                        ->label('Valor (manual)')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('about_stat_2_source') ?: 'reviews_count') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('about_stat_2_source') ?: 'reviews_count') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('about_stat_2_label_es')->label('Etiqueta (ES)')->placeholder('Opiniones de viajeros'),
                                    TextInput::make('about_stat_2_label_en')->label('Etiqueta (EN)')->placeholder('Traveler reviews'),
                                    TextInput::make('about_stat_2_label_pt')->label('Etiqueta (PT)')->placeholder('Avaliações de viajantes'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Indicador 3 — Tours & experiencias')->columns(3)->schema([
                                    Select::make('about_stat_3_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('tours_count')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('about_stat_3_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Mapa (por defecto)')->native(false),
                                    TextInput::make('about_stat_3_value')
                                        ->label('Valor (manual)')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('about_stat_3_source') ?: 'tours_count') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('about_stat_3_source') ?: 'tours_count') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('about_stat_3_label_es')->label('Etiqueta (ES)')->placeholder('Tours & experiencias'),
                                    TextInput::make('about_stat_3_label_en')->label('Etiqueta (EN)')->placeholder('Tours & experiences'),
                                    TextInput::make('about_stat_3_label_pt')->label('Etiqueta (PT)')->placeholder('Tours & experiências'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Indicador 4 — Destinos')->columns(3)->schema([
                                    Select::make('about_stat_4_source')
                                        ->label('Fuente del valor')
                                        ->options(self::STAT_SOURCE_OPTIONS)
                                        ->default('destinations_count')
                                        ->native(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    Select::make('about_stat_4_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Pin de ubicación (por defecto)')->native(false),
                                    TextInput::make('about_stat_4_value')
                                        ->label('Valor (manual)')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get): bool => ($get('about_stat_4_source') ?: 'destinations_count') !== 'manual')
                                        ->dehydrated(true)
                                        ->helperText(fn (Get $get): ?string => ($get('about_stat_4_source') ?: 'destinations_count') !== 'manual'
                                            ? '⚠️ Ignorado: la fuente elegida arriba calcula el valor automáticamente.'
                                            : null),
                                    TextInput::make('about_stat_4_label_es')->label('Etiqueta (ES)')->placeholder('Destinos'),
                                    TextInput::make('about_stat_4_label_en')->label('Etiqueta (EN)')->placeholder('Destinations'),
                                    TextInput::make('about_stat_4_label_pt')->label('Etiqueta (PT)')->placeholder('Destinos'),
                                ]),
                                \Filament\Forms\Components\Fieldset::make('Indicador 5 — Valores que nos guían')->columns(3)->schema([
                                    \Filament\Forms\Components\Placeholder::make('about_stat_5_note')
                                        ->label('')
                                        ->content('El valor SIEMPRE es el conteo real de los tags de Misión/Visión/Valores que ya se editan en Nosotros (Filament → Páginas → Nosotros → bloque Misión/Visión/Valores). Aquí solo se edita cómo se etiqueta y qué ícono lleva.')
                                        ->columnSpanFull(),
                                    Select::make('about_stat_5_icon')->label('Ícono')->options(\App\Support\HeroIcons::options())->placeholder('Corazón (por defecto)')->native(false),
                                    TextInput::make('about_stat_5_label_es')->label('Etiqueta (ES)')->placeholder('Valores que nos guían'),
                                    TextInput::make('about_stat_5_label_en')->label('Etiqueta (EN)')->placeholder('Values that guide us'),
                                    TextInput::make('about_stat_5_label_pt')->label('Etiqueta (PT)')->placeholder('Valores que nos guiam'),
                                ]),
                            ]),
                    ]),
                    // ── Blog: a diferencia de Contacto/Nosotros, el blog no tiene
                    // fila en la tabla `pages` (BlogController@index no resuelve
                    // ningún Page) — mismo caso que Home. Se administra aquí, con
                    // el mismo criterio de "vacío = texto por defecto del mockup".
                    Tabs\Tab::make('Blog')->icon('heroicon-o-newspaper')->schema([
                        \Filament\Forms\Components\Section::make('Hero')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('blog_hero_eyebrow_es')->label('Eyebrow (ES)')->placeholder('Inspírate para viajar'),
                                TextInput::make('blog_hero_eyebrow_en')->label('Eyebrow (EN)')->placeholder('Get inspired to travel'),
                                TextInput::make('blog_hero_eyebrow_pt')->label('Eyebrow (PT)')->placeholder('Inspire-se para viajar'),
                                TextInput::make('blog_hero_title_es')->label('H1 (ES)')->placeholder('Blog de viajes'),
                                TextInput::make('blog_hero_title_en')->label('H1 (EN)')->placeholder('Travel blog'),
                                TextInput::make('blog_hero_title_pt')->label('H1 (PT)')->placeholder('Blog de viagens'),
                                Textarea::make('blog_hero_sub_es')->label('Bajada (ES)')->rows(2)->placeholder('Consejos, guías y experiencias para que disfrutes al máximo tu aventura por el Perú.')->columnSpanFull(),
                                Textarea::make('blog_hero_sub_en')->label('Bajada (EN)')->rows(2)->placeholder('Tips, guides and experiences to help you make the most of your adventure in Peru.')->columnSpanFull(),
                                Textarea::make('blog_hero_sub_pt')->label('Bajada (PT)')->rows(2)->placeholder('Dicas, guias e experiências para você aproveitar ao máximo sua aventura pelo Peru.')->columnSpanFull(),
                            ]),
                        \Filament\Forms\Components\Section::make('Buscador y título de sección')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('blog_search_placeholder_es')->label('Placeholder buscador (ES)')->placeholder('Buscar artículos, destinos o consejos…'),
                                TextInput::make('blog_search_placeholder_en')->label('Search placeholder (EN)')->placeholder('Search articles, destinations or tips…'),
                                TextInput::make('blog_search_placeholder_pt')->label('Placeholder de busca (PT)')->placeholder('Buscar artigos, destinos ou dicas…'),
                                TextInput::make('blog_toolbar_title_es')->label('Título de sección (ES)')->placeholder('Explora nuestros artículos'),
                                TextInput::make('blog_toolbar_title_en')->label('Section title (EN)')->placeholder('Explore our articles'),
                                TextInput::make('blog_toolbar_title_pt')->label('Título da seção (PT)')->placeholder('Explore nossos artigos'),
                            ]),
                        \Filament\Forms\Components\Section::make('CTA final')
                            ->collapsible()
                            ->columns(3)
                            ->schema([
                                TextInput::make('blog_cta_title_es')->label('Título (ES)')->placeholder('¿Listo para vivir tu propia historia?'),
                                TextInput::make('blog_cta_title_en')->label('Title (EN)')->placeholder('Ready to live your own story?'),
                                TextInput::make('blog_cta_title_pt')->label('Título (PT)')->placeholder('Pronto para viver sua própria história?'),
                                Textarea::make('blog_cta_desc_es')->label('Bajada (ES)')->rows(2)->placeholder('Inspírate, planea y reserva tu próxima aventura con nosotros.'),
                                Textarea::make('blog_cta_desc_en')->label('Subtitle (EN)')->rows(2)->placeholder('Get inspired, plan and book your next adventure with us.'),
                                Textarea::make('blog_cta_desc_pt')->label('Bajada (PT)')->rows(2)->placeholder('Inspire-se, planeje e reserve sua próxima aventura conosco.'),
                                TextInput::make('blog_cta_btn_primary_es')->label('Botón rojo (ES)')->placeholder('Ver tours disponibles'),
                                TextInput::make('blog_cta_btn_primary_en')->label('Red button (EN)')->placeholder('See available tours'),
                                TextInput::make('blog_cta_btn_primary_pt')->label('Botão vermelho (PT)')->placeholder('Ver tours disponíveis'),
                                TextInput::make('blog_cta_btn_wa_es')->label('Botón WhatsApp (ES)')->placeholder('Habla con un asesor'),
                                TextInput::make('blog_cta_btn_wa_en')->label('WhatsApp button (EN)')->placeholder('Talk to an advisor'),
                                TextInput::make('blog_cta_btn_wa_pt')->label('Botão WhatsApp (PT)')->placeholder('Fale com um consultor'),
                            ]),
                    ]),
                    Tabs\Tab::make('GEO')->icon('heroicon-o-map-pin')->schema([
                        // docs/qa/F7-personas.md §labels #2 / §e: esta pestaña es puramente
                        // técnica para un usuario no experto; se agrega una explicación en
                        // lenguaje llano en vez de reordenar/ocultar (sigue siendo útil para
                        // quien la necesite editar).
                        \Filament\Forms\Components\Placeholder::make('geo_help')
                            ->label('¿Qué es esto?')
                            ->content('Estos datos ayudan a que Google Maps y los buscadores muestren correctamente la ubicación de tu negocio (Ubicación en el mapa). Si no sabes qué poner, puedes dejarlo como está.')
                            ->columnSpanFull(),
                        TextInput::make('geo_business_name')
                            ->label('Nombre del negocio')
                            ->default('Lima América Tours')
                            ->helperText('Nombre oficial que aparece en Google Maps y buscadores.'),
                        TextInput::make('geo_street')
                            ->label('Calle / Dirección exacta')
                            ->placeholder('Av. Larco 1301, Miraflores'),
                        TextInput::make('geo_city')
                            ->label('Ciudad')
                            ->default('Lima'),
                        TextInput::make('geo_region')
                            ->label('Región / Departamento')
                            ->default('Lima'),
                        TextInput::make('geo_postal_code')
                            ->label('Código postal'),
                        TextInput::make('geo_country')
                            ->label('País (código ISO 2)')
                            ->default('PE')
                            ->maxLength(2),
                        TextInput::make('geo_latitude')
                            ->label('Latitud')
                            ->placeholder('-12.1091800')
                            ->helperText('Decimal. Ej: -12.1091800 (copia de Google Maps)'),
                        TextInput::make('geo_longitude')
                            ->label('Longitud')
                            ->placeholder('-77.0365200'),
                        TextInput::make('geo_region_code')
                            ->label('Código de región (ISO 3166-2)')
                            ->default('PE-LIM')
                            ->placeholder('PE-LIM')
                            ->helperText('Formato ISO 3166-2. Ej: PE-LIM para Lima, Perú.'),
                        TextInput::make('geo_price_range')
                            ->label('Rango de precio')
                            ->default('$$')
                            ->placeholder('$$')
                            ->helperText('Símbolo para schema.org. Ej: $ = económico, $$$$ = lujo.'),
                    ]),
                    Tabs\Tab::make('AEO / FAQ')->icon('heroicon-o-question-mark-circle')->schema([
                        \Filament\Forms\Components\Placeholder::make('aeo_help')
                            ->label('¿Qué es esto?')
                            ->content('Aquí escribes las Preguntas frecuentes que Google puede mostrar directamente en los resultados de búsqueda (lo técnico se llama "AEO"/schema FAQ, pero para ti es simplemente: preguntas y respuestas).')
                            ->columnSpanFull(),
                        Repeater::make('faqs')
                            ->label('Preguntas Frecuentes (FAQs)')
                            ->helperText('Estas FAQs se muestran en el sitio como acordeón y se incluyen en el schema FAQ (AEO).')
                            ->schema([
                                TextInput::make('question_es')->label('Pregunta (ES)')->required()->columnSpanFull(),
                                TextInput::make('question_en')->label('Pregunta (EN)')->columnSpanFull(),
                                TextInput::make('question_pt')->label('Pregunta (PT)')->columnSpanFull(),
                                Textarea::make('answer_es')->label('Respuesta (ES)')->rows(3)->required()->columnSpanFull(),
                                Textarea::make('answer_en')->label('Respuesta (EN)')->rows(3)->columnSpanFull(),
                                Textarea::make('answer_pt')->label('Respuesta (PT)')->rows(3)->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['question_es'] ?? null)
                            ->addActionLabel('+ Agregar pregunta')
                            ->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('APIs')->icon('heroicon-o-key')->schema([
                        \Filament\Forms\Components\Placeholder::make('apis_help')
                            ->label('¿Qué es esto?')
                            ->content('Conexiones externas: claves técnicas para conectar el sitio con Google Maps, Google Reseñas y Tripadvisor. Si no las tienes a mano, puedes dejar esta pestaña vacía y pedírselas a quien administre esas cuentas.')
                            ->columnSpanFull(),
                        TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->columnSpanFull()
                            ->helperText('Habilita Places API + Maps JavaScript API en Google Cloud, con billing. Activa el autocompletado de hotel en el checkout.'),
                        TextInput::make('google_place_id')
                            ->label('Google Place ID')
                            ->columnSpanFull()
                            ->helperText('ID del negocio en Google Maps, para traer reseñas. Ej: ChIJN1t_tDeuEmsRUsoyG83frY4'),
                        Toggle::make('google_reviews_enabled')
                            ->label('Activar traída de reseñas de Google')
                            ->helperText('Requiere Google Maps API Key y Place ID configurados.'),
                        TextInput::make('tripadvisor_api_key')
                            ->label('Tripadvisor API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->columnSpanFull()
                            ->helperText('Clave de la Content API de Tripadvisor. Requiere aprobación previa de Tripadvisor.'),
                        TextInput::make('tripadvisor_location_id')
                            ->label('Tripadvisor Location ID')
                            ->columnSpanFull()
                            ->helperText('ID numérico del negocio en Tripadvisor. Se encuentra en la URL del perfil.'),
                        Toggle::make('tripadvisor_reviews_enabled')
                            ->label('Activar traída de reseñas de Tripadvisor')
                            ->helperText('Requiere Tripadvisor API Key y Location ID configurados.'),

                        // 2026-08-15 — Los textos de ayuda de esta sección PROMETÍAN algo
                        // que no ocurre: decían "se muestra en la tarjeta de Google /
                        // Tripadvisor de la página de cada tour", y esa tarjeta no existe
                        // hoy en tours/show.blade.php (el rating que sí se ve ahí sale de
                        // `$tour->rating`). Hallazgo del recorrido campo por campo del
                        // panel. Se corrige el TEXTO, no el comportamiento: decidir si se
                        // construye esa tarjeta o si estas cuatro claves se retiran es
                        // alcance, y lo decide Anyerson — pero mientras tanto el panel no
                        // puede afirmarle a la clienta que un campo hace algo que no hace.
                        //
                        // Las cifras que SÍ se publican hoy son las de "Reseñas externas
                        // verificadas" (`reviews_external_*`), que consume
                        // HomeStatsResolver para la card de stats del hero.
                        \Filament\Forms\Components\Section::make('Tarjetas de reseñas (rating y nº) — sin uso hoy')
                            ->description('⚠️ Estos cuatro campos NO se publican en ninguna pantalla por ahora: la tarjeta de Google/Tripadvisor en la ficha de tour todavía no existe. Para que una cifra de reseñas se vea en el sitio, usá la sección "Reseñas externas verificadas".')
                            ->collapsed()
                            ->collapsible()
                            ->schema([
                                TextInput::make('reviews_google_rating')
                                    ->label('Google — Rating (ej. 4.9)')
                                    ->helperText('Guardado, pero sin efecto en el sitio por ahora.'),
                                TextInput::make('reviews_google_count')
                                    ->label('Google — Nº de reseñas (ej. 123)')
                                    ->numeric()
                                    ->helperText('Guardado, pero sin efecto en el sitio por ahora.'),
                                TextInput::make('reviews_tripadvisor_rating')
                                    ->label('Tripadvisor — Rating (ej. 4.6)')
                                    ->helperText('Guardado, pero sin efecto en el sitio por ahora.'),
                                TextInput::make('reviews_tripadvisor_count')
                                    ->label('Tripadvisor — Nº de reseñas (ej. 8)')
                                    ->numeric()
                                    ->helperText('Guardado, pero sin efecto en el sitio por ahora.'),
                            ]),
                    ]),
                    Tabs\Tab::make('Recogida')->icon('heroicon-o-map')->schema([
                        Toggle::make('pickup_enabled')
                            ->label('¿Ofrece servicio de recogida en zonas generales?')
                            ->helperText('Actívalo para mostrar la sección "¿Dónde te recogemos?" en la página de CADA tour, con el mapa y las zonas configuradas abajo. Es una configuración global: aplica igual para todos los tours.')
                            ->live(),

                        \Filament\Forms\Components\Section::make('Zonas de recogida')
                            ->description('Define las zonas donde ofreces recogida. Se muestran en todos los tours. Reutiliza la Google Maps API Key configurada en la pestaña "APIs".')
                            ->collapsible()
                            ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('pickup_enabled'))
                            ->schema([
                                \Filament\Forms\Components\ViewField::make('pickup_zones_tools')
                                    ->view('filament.pages.partials.pickup-zones-tools')
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                Repeater::make('pickup_zones')
                                    ->label('Zonas')
                                    ->helperText('Escribe el nombre del lugar para usar el autocompletado de Google (rellena lat/lng automáticamente). Si el autocompletado no responde, puedes ingresar lat/lng a mano: en Google Maps, clic derecho sobre el punto exacto → clic en las coordenadas para copiarlas.')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Nombre del lugar / zona')
                                            ->placeholder('Miraflores, Lima')
                                            ->required()
                                            ->columnSpanFull()
                                            ->extraInputAttributes([
                                                'data-pickup-place-input' => 'true',
                                                'autocomplete' => 'off',
                                                'x-init' => 'window.__lvtInitPickupAutocomplete && window.__lvtInitPickupAutocomplete($el)',
                                            ]),
                                        TextInput::make('lat')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->step('any')
                                            ->required()
                                            ->extraInputAttributes(['data-pickup-lat' => 'true']),
                                        TextInput::make('lng')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->step('any')
                                            ->required()
                                            ->extraInputAttributes(['data-pickup-lng' => 'true']),
                                        TextInput::make('radius_km')
                                            ->label('Radio (km)')
                                            ->numeric()
                                            ->step('0.1')
                                            ->minValue(0.1)
                                            ->default(2)
                                            ->required(),
                                        Select::make('type')
                                            ->label('Tipo de recogida')
                                            ->options([
                                                'all' => 'Todas las ubicaciones',
                                                'hotels' => 'Solo hoteles',
                                            ])
                                            ->default('all')
                                            ->native(false)
                                            ->required(),
                                    ])
                                    ->columns(4)
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->addActionLabel('Añadir otra zona')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                    Tabs\Tab::make('reCAPTCHA')->icon('heroicon-o-shield-check')->schema([
                        \Filament\Forms\Components\Placeholder::make('recaptcha_help')
                            ->label('¿Qué es esto?')
                            ->content('reCAPTCHA es la protección anti-robots en los formularios del sitio (contacto, reservas): evita que bots automatizados los llenen con spam.')
                            ->columnSpanFull(),
                        Toggle::make('recaptcha_enabled')
                            ->label('Activar reCAPTCHA')
                            ->helperText('Protege los formularios públicos contra bots. Requiere las claves de Google configuradas abajo.')
                            ->live(),
                        Select::make('recaptcha_version')
                            ->label('Versión')
                            ->options([
                                'v3' => 'v3 — Invisible (recomendado, sin interrupción al usuario)',
                                'v2' => 'v2 — Casilla "No soy un robot"',
                            ])
                            ->default('v3')
                            ->native(false)
                            ->required()
                            ->live()
                            ->helperText('v3 actúa en segundo plano. v2 muestra un widget visible al usuario.'),
                        TextInput::make('recaptcha_site_key')
                            ->label('Site Key (pública)')
                            ->columnSpanFull()
                            ->autocomplete(false)
                            ->helperText('Se obtiene en https://www.google.com/recaptcha/admin — clave pública que va en el frontend.'),
                        TextInput::make('recaptcha_secret_key')
                            ->label('Secret Key (privada)')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->autocomplete('new-password')
                            ->helperText('Clave privada que se usa en el servidor para verificar los tokens. Nunca exponerla en el frontend.'),
                        TextInput::make('recaptcha_v3_threshold')
                            ->label('Umbral de puntuación v3 (0.0 – 1.0)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.05)
                            ->default(0.5)
                            ->visible(fn (\Filament\Forms\Get $get): bool => $get('recaptcha_version') === 'v3')
                            ->helperText('Puntuaciones más cercanas a 1.0 son probablemente humanos; cerca de 0.0, bots. El valor recomendado es 0.5.'),
                    ]),
                    Tabs\Tab::make('Cookies')->icon('heroicon-o-shield-exclamation')->schema([
                        Toggle::make('cookie_banner_enabled')
                            ->label('Mostrar banner de consentimiento de cookies')
                            ->helperText(
                                'Activo: el banner se muestra a usuarios sin decisión previa y el analytics '.
                                'requiere consentimiento (Google Consent Mode v2 + FB Pixel gating). '.
                                'Inactivo: el analytics carga directamente sin pedir consentimiento.'
                            )
                            ->default(true),
                        Textarea::make('cookie_text_es')
                            ->label('Texto del banner (ES)')
                            ->rows(3)
                            ->placeholder('Usamos cookies para mejorar tu experiencia y analizar el tráfico del sitio. Puedes aceptarlas o rechazarlas.')
                            ->helperText('Deja en blanco para usar el texto por defecto en español.')
                            ->columnSpanFull(),
                        Textarea::make('cookie_text_en')
                            ->label('Texto del banner (EN)')
                            ->rows(3)
                            ->placeholder('We use cookies to improve your experience and analyze site traffic. You can accept or reject them.')
                            ->helperText('Leave blank to use the default English text.')
                            ->columnSpanFull(),
                        Textarea::make('cookie_text_pt')
                            ->label('Texto del banner (PT)')
                            ->rows(3)
                            ->placeholder('Usamos cookies para melhorar sua experiência e analisar o tráfego do site. Você pode aceitá-las ou recusá-las.')
                            ->helperText('Deixe em branco para usar o texto padrão em português.')
                            ->columnSpanFull(),
                    ]),
                ]),
            ])
            ->statePath('data');
    }

    /**
     * Opciones del selector "Fuente del valor" de cada slot de la barra de
     * estadísticas. Deben coincidir exactamente con
     * App\Services\HomeStatsResolver::SOURCES.
     */
    private const STAT_SOURCE_OPTIONS = [
        'manual' => 'Manual (texto libre)',
        'rating_real' => 'Calculado: rating real de las reseñas (esta base de datos)',
        'reviews_count' => 'Calculado: cantidad de reseñas (esta base de datos)',
        'rating_external' => 'Verificado: rating externo (Google/Tripadvisor, cargado abajo)',
        'reviews_external_count' => 'Verificado: cantidad de opiniones externas (Google/Tripadvisor, cargada abajo)',
        'tours_count' => 'Calculado: tours publicados',
        'years_active' => 'Calculado: años de operación',
        'destinations_count' => 'Calculado: destinos con tours publicados',
    ];

    /** Keys whose values are stored as boolean type in settings. */
    private const BOOLEAN_KEYS = [
        'google_reviews_enabled',
        'tripadvisor_reviews_enabled',
        'recaptcha_enabled',
        'cookie_banner_enabled',
        'pickup_enabled',
        'home_stats_enabled',
        'home_news_enabled',
        'about_stats_enabled',
    ];

    /**
     * Hosts que NO deben aceptarse como URL absoluta en campos de "destino"
     * opcionales que tienen fallback a una ruta interna (p.ej. el CTA
     * primario del hero). Incluye el dominio real de producción (hardcoded
     * a propósito: el host de config('app.url') en staging/local es
     * 127.0.0.1/otro, y necesitamos bloquear el dominio de PRODUCCIÓN
     * incluso cuando se edita desde local) y el símétrico localhost/
     * 127.0.0.1 (para no colar por descuido una URL de pruebas hacia
     * producción). Ver hallazgo CRO 2026-08-11: home_hero_cta_primary_url
     * traía "https://limaamericatours.com/tours" hardcodeado, y ese mismo
     * dato sacaba al visitante de staging/local hacia producción.
     */
    private static function selfOrLocalHosts(): array
    {
        $hosts = ['limaamericatours.com', 'www.limaamericatours.com', 'localhost', '127.0.0.1'];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost) {
            $hosts[] = strtolower($appHost);
        }

        return array_unique(array_map('strtolower', $hosts));
    }

    /**
     * Regla compartida por los campos "URL destino" de los botones del hero
     * (primario y secundario): acepta ruta relativa ("/tours") o URL externa
     * completa, pero rechaza una URL completa que apunte a este mismo sitio
     * (ver selfOrLocalHosts()). Antes vivía duplicada solo en el CTA primario;
     * extraída para que el secundario ("Ver Tours", 2026-08-15) tenga la MISMA
     * validación sin copiar el closure entero.
     */
    private static function relativeOrExternalUrlRule(): \Closure
    {
        return function () {
            return function (string $attribute, $value, \Closure $fail) {
                $value = trim((string) $value);
                if ($value === '') {
                    return;
                }

                $host = parse_url($value, PHP_URL_HOST);
                if (! $host) {
                    // Sin host: ruta relativa (ej. "/tours"). Debe
                    // empezar con "/" — cualquier otra cosa no es ni
                    // una ruta ni una URL válida.
                    if (! str_starts_with($value, '/')) {
                        $fail('Escribe una ruta relativa que empiece con "/" (ej. "/tours") o una URL completa a una web externa (ej. "https://...").');
                    }

                    return;
                }

                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail('Esa URL no es válida.');

                    return;
                }

                if (in_array(strtolower($host), self::selfOrLocalHosts(), true)) {
                    $fail("No pegues la URL completa de este mismo sitio ({$host}). Escribe solo la ruta relativa, por ejemplo \"/tours\". Una URL completa solo es válida si el destino es una web externa de verdad.");
                }
            };
        };
    }

    public function save(): void
    {
        // getState() deshidrata el formulario: procesa los FileUpload (mueve los
        // archivos temporales al disco configurado y devuelve la RUTA), también
        // los anidados dentro de Repeaters. Iterar $this->data crudo dejaba el
        // estado de FilePond sin resolver ({uuid:{}}) y no guardaba la imagen.
        $data = $this->form->getState();

        // Moneda del sitio: se lee ANTES de guardar para saber si cambió.
        $currencyBefore = strtoupper((string) Setting::get('site_currency', config('services.site_currency', 'USD')));

        foreach ($data as $key => $value) {
            // Serialize Repeater fields as JSON string
            $jsonRepeaterKeys = ['faqs', 'home_destinos', 'home_why_items', 'home_tour_type_tabs',
                'home_footer_features', 'home_exp_tours', 'home_reco_items', 'home_faqs',
                'pickup_zones'];
            if (in_array($key, $jsonRepeaterKeys, true)) {
                Setting::set($key, json_encode(is_array($value) ? $value : []));

                continue;
            }
            // Store boolean toggle fields with the correct type so castValue works
            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                Setting::set($key, $value ? '1' : '0', 'boolean');

                continue;
            }
            Setting::set($key, $value);
        }

        $this->realignTourCurrency($currencyBefore, $data['site_currency'] ?? null);

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }

    /**
     * Cambiar "Moneda del sitio" re-etiqueta los tours que estaban en la moneda
     * anterior. NO convierte importes.
     *
     * Por qué existe esto (hallazgo de QA, 2026-07-29): el Setting era editable
     * sin nada que lo mantuviera alineado con la columna `tours.currency`.
     * Poniendo el sitio en soles con los 33 tours etiquetados en dólares, el
     * front pasaba a mostrar "S/" pero la guarda del checkout
     * (CartService::isSiteCurrencyOnly) rechazaba TODOS los tours: el cobro en
     * línea quedaba muerto para el catálogo entero, con un mensaje genérico y
     * sin nada en pantalla que explicara por qué. Un clic en un select del
     * panel no puede apagar el checkout en silencio.
     *
     * Solo re-etiqueta los tours que tenían exactamente la moneda anterior: uno
     * cargado a mano en una tercera moneda se queda como está y el checkout lo
     * sigue rechazando, que es lo correcto.
     *
     * El aviso dice explícitamente que los precios NO se convierten, porque es
     * la parte que se malinterpreta: 720 pasa de $720 a S/ 720, no a S/ 2,700.
     */
    private function realignTourCurrency(string $before, mixed $after): void
    {
        $after = strtoupper(trim((string) $after));

        if ($after === '' || $after === $before) {
            return;
        }

        $retagged = \App\Models\Tour::where('currency', $before)->update(['currency' => $after]);

        \Illuminate\Support\Facades\Log::info('settings.site_currency.changed', [
            'from' => $before,
            'to' => $after,
            'tours_retagged' => $retagged,
        ]);

        Notification::make()
            ->title("Moneda cambiada de {$before} a {$after}")
            ->body($retagged === 0
                ? 'Ningún tour estaba en la moneda anterior, así que no se re-etiquetó nada. Revisa la moneda de cada tour si el checkout rechaza reservas.'
                : "Se re-etiquetaron {$retagged} tour(s) a {$after}. OJO: los precios NO se convirtieron — el número es el mismo, solo cambió la moneda (ej. 720 pasa de \$720 a S/ 720).")
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar cambios')
                ->submit('save'),
        ];
    }
}
