<?php

return [
    // Título de la columna "Suscríbete" del footer (2026-08-14).
    'newsletter_cta' => 'Subscribe',
    'newsletter_eyebrow' => 'Subscribe to get up to date information, news and updates.',
    'newsletter_title' => 'Sign up for our newsletter to receive our news, deals and special offers.',
    'newsletter_name' => 'Name',
    'newsletter_email' => 'Email',
    'newsletter_submit' => 'Send',
    'links' => 'Links',
    'about_short' => 'Us',
    'terms' => 'Terms and Conditions',
    'privacy' => 'Privacy Policy',
    'esnna' => 'ESNNA code of conduct',
    'locate_us' => 'Locate us at',
    // 'address' and 'hours' were removed 2026-08-11: a THIRD hardcoded
    // address/hours, different from the panel (Settings → Contact) and from
    // Terms/Privacy. Single source now: Setting::contactAddress()/contactHours().
    'follow_us' => 'Follow us',
    'methods_of_payment' => 'Methods of payment',
    'brand_description' => 'Our company distinguishes itself by offering unique and vibrant experiences that transcend the ordinary. Every meticulously designed detail of our approach reflects the richness and diversity of our experiences, inspired by the rich Peruvian culture.',
    'popular_tours' => 'Popular Tours',
    // 'seal_secure_payment' removed 2026-08-12: v1 scope has no active
    // payment gateway (PayPal disabled server-side, Culqi awaiting client
    // keys; bookings close via WhatsApp/email). Promising "secure payment"
    // would be a false claim — see docs/rebrand/LOTE-MOCKUPS-AGO-2026.md.
    // ── Trust strip (2026-08-21) — see the note in lang/es/footer.php ─────
    'trust_title' => 'Certifications and reviews',
    'tripadvisor_aria' => 'Rated :rating out of 5 on Tripadvisor, :count reviews. Opens the profile in a new tab.',
    'tripadvisor_reviews' => ':count reviews',
    'registry_seal_alt' => 'Registered travel and tourism agency',
    'esnna_seal_alt' => 'Commitment against the sexual exploitation of children and adolescents (ESNNA)',
    'ruc_label' => 'Tax ID (RUC)',
    'seal_best_price' => 'Best Price Guaranteed',
    'seal_responsible' => 'Responsible Travel',
    'rights_reserved_by' => 'All rights reserved.',
];
