@php
    $settings   = $siteSettings ?? [];
    $locale     = app()->getLocale();
    // SEO S-02: usar "?:" en vez de "??" para estos fallbacks. Las claves de
    // Settings existen en BD con valor '' (string vacío), no null, así que
    // "??" nunca disparaba el default (solo lo hace con null/ausente). "?:"
    // sí trata '' como "sin valor" y cae al fallback correctamente.
    // Nota: se antepone "?? ''" a cada acceso directo al array para no emitir
    // "Undefined array key" cuando la clave todavía no existe en Settings.
    $siteName   = ($settings['site_name'] ?? '')   ?: __('seo.site_name');
    $description= ($settings['site_description_' . $locale] ?? '')
                  ?: (($settings['site_description_es'] ?? '') ?: __('seo.default_description'));
    $email      = ($settings['contact_email'] ?? '')   ?: 'hola@limaamericatours.com';
    // Sin fallback a otro número de contacto: ver App\Models\Setting::contactPhone().
    // Un "telephone" falso o heredado de otro cliente en el schema que lee
    // Google es peor que no declarar la propiedad.
    $waDigits   = \App\Models\Setting::whatsappNumber();
    $telephone  = $waDigits ? ('+' . $waDigits) : \App\Models\Setting::contactPhone();
    // RUC: sin default. El footer y Términos llegaron a publicar DOS RUC
    // contradictorios (20616108264 "Viaja con LAT S.A.C." / 10720481826
    // "Díaz Córdova Augusto Manuel"), ninguno confirmado — ver
    // App\Models\Setting::companyRuc() y docs/rebrand/ESTADO.md.
    $companyRuc = \App\Models\Setting::companyRuc();

    // GEO settings
    $geoName    = ($settings['geo_business_name'] ?? '') ?: $siteName;
    // Dirección real: prioriza geo_street (campo dedicado del tab GEO); si está
    // vacío, cae a la dirección real ya cargada en el tab Contacto (Setting::
    // contactAddress(), con su propio fallback ES). SIN "Lima" como último
    // recurso desde 2026-08-11: "Lima" no es una calle, y las dos direcciones
    // que circulaban antes eran de otro cliente o sin confirmar (ver
    // docs/rebrand/ESTADO.md). Sin dato real, `streetAddress` se omite del
    // schema en vez de imprimir un valor sin sentido.
    $geoStreet  = ($settings['geo_street'] ?? '') ?: \App\Models\Setting::contactAddress($locale);
    $geoCity    = ($settings['geo_city'] ?? '')          ?: 'Lima';
    $geoRegion  = ($settings['geo_region'] ?? '')        ?: 'Lima';
    $geoPostal  = $settings['geo_postal_code']   ?? null;
    $geoCountry = ($settings['geo_country'] ?? '')       ?: 'PE';
    $geoLat     = $settings['geo_latitude']      ?? null;
    $geoLong    = $settings['geo_longitude']     ?? null;
    $geoPrice   = ($settings['geo_price_range'] ?? '')   ?: '$$';

    // Opening hours: derived from contact_hours setting if available
    $rawHours   = ($settings['contact_hours_' . $locale] ?? '')
                  ?: (($settings['contact_hours_es'] ?? '') ?: null);

    // Social links for sameAs.
    // OJO: no usar ltrim($url, 'https://') — recorta por juego de caracteres
    // y convierte "tiktok.com" en "iktok.com".
    $stripScheme = static fn (?string $url): ?string => $url
        ? 'https://' . preg_replace('#^https?://#i', '', trim($url))
        : null;
    $sameAs = array_values(array_filter([
        $stripScheme($settings['social_instagram'] ?? null),
        $stripScheme($settings['social_facebook'] ?? null),
        $stripScheme($settings['social_tiktok'] ?? null),
        $stripScheme($settings['social_youtube'] ?? null),
        $settings['social_google_reviews']   ?? null,
        $settings['social_tripadvisor']      ?? null,
        $settings['social_trivago']          ?? null,
    ]));

    // Build TravelAgency / LocalBusiness schema
    // Nota: sin aggregateRating aquí — Google considera "self-serving" las reseñas
    // propias marcadas sobre LocalBusiness/Organization y lo reporta como error en GSC.
    $organization = [
        '@context' => 'https://schema.org',
        '@type'    => ['TravelAgency', 'LocalBusiness'],
        'name'     => $geoName,
        'url'      => url('/' . $locale),
        'logo'     => asset('assets/logos/logo-america-original.webp'),
        'image'    => asset('assets/banners/banner-hero.jpg'),
        'description' => $description,
        'email'    => $email,
        'priceRange' => $geoPrice,
        'address'  => array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $geoStreet,
            'addressLocality' => $geoCity,
            'addressRegion'   => $geoRegion,
            'postalCode'      => $geoPostal,
            'addressCountry'  => $geoCountry,
        ]),
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Lima'],
            ['@type' => 'City', 'name' => 'Cusco'],
            ['@type' => 'City', 'name' => 'Ica'],
            ['@type' => 'City', 'name' => 'Paracas'],
        ],
        'inLanguage' => ['es-PE', 'en-US', 'pt-BR'],
    ];

    // Omitir "telephone" del schema si no hay dato — ver comentario arriba.
    if ($telephone) {
        $organization['telephone'] = $telephone;
    }

    if ($companyRuc) {
        $organization['taxID'] = $companyRuc;
    }

    // Add geo coordinates only when available
    if ($geoLat && $geoLong) {
        $organization['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $geoLat,
            'longitude' => (float) $geoLong,
        ];
    }

    // Opening hours (raw string, e.g. "Lunes a Domingo 7am-10pm")
    if ($rawHours) {
        $organization['openingHours'] = $rawHours;
    }

    if (! empty($sameAs)) {
        $organization['sameAs'] = $sameAs;
    }

    $website = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => $siteName,
        'url'      => url('/'),
        'inLanguage' => $locale,
        'potentialAction' => [
            '@type'        => 'SearchAction',
            'target'       => url('/' . $locale . '/buscar?q={search_term_string}'),
            'query-input'  => 'required name=search_term_string',
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
<script type="application/ld+json">{!! json_encode($website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
