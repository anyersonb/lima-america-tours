<?php

return [
    // Título de la columna "Suscríbete" del footer (2026-08-14, footer de 5
    // columnas). 'newsletter_eyebrow' quedó sin uso al desaparecer la banda de
    // newsletter que había encima del footer; se conserva la clave por si vuelve
    // a hacer falta en otra pantalla.
    'newsletter_cta' => 'Suscríbete',
    'newsletter_eyebrow' => 'Suscríbete para recibir información actualizada, novedades y ofertas.',
    'newsletter_title' => 'Suscríbete a nuestro boletín para recibir noticias, ofertas y promociones especiales.',
    'newsletter_name' => 'Nombre',
    'newsletter_email' => 'Correo',
    'newsletter_submit' => 'Enviar',
    'links' => 'Enlaces',
    'about_short' => 'Nosotros',
    'terms' => 'Términos y condiciones',
    'privacy' => 'Política de privacidad',
    'esnna' => 'Código de conducta ESNNA',
    'locate_us' => 'Encuéntranos en',
    // 'address' y 'hours' se retiraron 2026-08-11: eran una TERCERA dirección/
    // horario hardcodeados, distintos de lo que el cliente carga en el panel
    // (Configuración → Contacto) y de lo que decían Términos/Privacidad. Única
    // fuente ahora: App\Models\Setting::contactAddress()/contactHours(). Sin
    // dato en el panel, el <li> correspondiente del footer se oculta.
    'follow_us' => 'Síguenos',
    'methods_of_payment' => 'Métodos de pago',
    'brand_description' => 'Nuestra empresa se distingue por ofrecer experiencias únicas y vibrantes que trascienden lo ordinario. Cada detalle, meticulosamente diseñado, refleja la riqueza y diversidad de nuestras experiencias, inspiradas en la rica cultura peruana.',
    'popular_tours' => 'Tours Populares',
    // 'seal_secure_payment' se retiró 2026-08-12: el alcance v1 no tiene
    // pasarela de pago activa (PayPal apagado server-side, Culqi espera
    // llaves del cliente; la reserva se cierra por WhatsApp/correo).
    // Prometer "pago seguro" es afirmar algo falso — ver
    // docs/rebrand/LOTE-MOCKUPS-AGO-2026.md, tabla "Lo que NO se publica".
    // ── Franja de confianza (2026-08-21, pedido del jefe) ─────────────────
    // El bloque de Tripadvisor solo aparece con enlace + calificación +
    // cantidad de opiniones cargados (App\Support\TripadvisorBadge); los
    // sellos, solo si el cliente subió la imagen.
    'trust_title' => 'Certificaciones y reseñas',
    'tripadvisor_aria' => 'Calificación :rating de 5 en Tripadvisor, :count opiniones. Abre el perfil en una pestaña nueva.',
    'tripadvisor_reviews' => ':count reseñas',
    'registry_seal_alt' => 'Agencia de viajes y turismo registrada',
    'esnna_seal_alt' => 'Compromiso contra la explotación sexual de niñas, niños y adolescentes (ESNNA)',
    'ruc_label' => 'RUC',
    'seal_best_price' => 'Mejores Precios Garantizados',
    'seal_responsible' => 'Viajes Responsables',
    'rights_reserved_by' => 'Todos los derechos reservados.',
];
