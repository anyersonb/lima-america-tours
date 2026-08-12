<?php

return [
    'newsletter_eyebrow' => 'Suscríbete para recibir información actualizada, novedades y ofertas.',
    'newsletter_title' => 'Suscríbete a nuestro boletín para recibir noticias, ofertas y promociones especiales.',
    'newsletter_name' => 'Nombre',
    'newsletter_email' => 'Correo',
    'newsletter_submit' => 'Enviar',
    'links' => 'Enlaces',
    'about_short' => 'Nosotros',
    'terms' => 'Términos y condiciones',
    'privacy' => 'Política de privacidad',
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
    'seal_best_price' => 'Mejores Precios Garantizados',
    'seal_responsible' => 'Viajes Responsables',
    'rights_reserved_by' => 'Todos los derechos reservados.',
];
