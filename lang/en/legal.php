<?php

return [
    'terms_title' => 'Terms and Conditions',
    'privacy_title' => 'Privacy Policy',
    'last_updated' => 'Last updated: May 7, 2026',

    // Terms sections
    'terms_s1_title' => 'Acceptance of Terms',
    'terms_s1_body' => 'By accessing and using Lima América Tours services, you agree to be bound by these terms and conditions. If you disagree with any part of these terms, please do not use our services. Lima América Tours reserves the right to update these terms at any time, notifying users by publishing the new version on this site.',

    'terms_s2_title' => 'Description of Service',
    'terms_s2_body' => 'Lima América Tours is a company dedicated to organizing and marketing tours and travel experiences in Lima, Ica, Cusco and other regions of Peru. Our services include itinerary planning, transportation, certified tour guides and cultural activities. Prices, availability and itineraries are subject to change without prior notice for operational or force majeure reasons.',

    'terms_s3_title' => 'Bookings and Payments',
    'terms_s3_body' => 'When completing your booking, you may choose to pay immediately via PayPal (credit/debit card or PayPal balance) or reserve now and arrange payment afterwards. In both cases the booking is confirmed by email and, if needed, by WhatsApp, where our team verifies the tour details and coordinates payment. Lima América Tours does not store card data: when payment is made immediately, processing is handled entirely by PayPal, a certified payment provider.',

    'terms_s4_title' => 'Cancellations and Refunds',
    'terms_s4_body' => 'If your booking is pending payment ("book now, pay later"), you may cancel it at no cost at any time before the tour date, since no charge has been made. For bookings already paid via PayPal: cancellations made more than 48 hours before the tour date will receive an 80% refund of the amount paid; cancellations made between 24 and 48 hours before the tour are entitled to a 50% refund. No refunds will be made for cancellations less than 24 hours in advance or for no-shows at the departure point. In cases of force majeure (natural disasters, strikes, government restrictions), rescheduling will be offered at no additional charge.',

    'terms_s5_title' => 'Contact',
    // Restructured 2026-08-11: the single paragraph hardcoded a phone
    // (+51 935 542 384, belonging to no client of this agency), an hours
    // range and an address, published in three languages — see
    // App\Models\Setting::contactPhone()/contactHours()/contactAddress(),
    // the single source now. Each line only prints when the panel
    // (Settings → Contact) has that data.
    'terms_s5_intro' => 'For any queries related to these terms, you may contact us via the form on our contact page or by email.',
    'terms_s5_phone_line' => 'You may also call us at :phone.',
    'terms_s5_hours_line' => 'Our team is available :hours.',
    'terms_s5_address_line' => 'Lima América Tours — :address.',

    // Privacy sections
    'privacy_s1_title' => 'Data We Collect',
    'privacy_s1_body' => 'Lima América Tours collects only the data strictly necessary to provide our services. This includes: full name, email address and phone number when making a booking or subscribing to the newsletter; anonymous browsing data (analytics cookies) to improve the user experience; and, only when you choose to pay immediately, payment information which is processed directly by PayPal and is not stored on our servers.',

    'privacy_s2_title' => 'Use of Data',
    'privacy_s2_body' => 'We use your personal information exclusively to: confirm and manage bookings, send communications related to your tour, improve our services through aggregate analysis and, with your express consent, send you newsletters with offers and news. We will not use your data for automated decision-making or profiling without your consent.',

    'privacy_s3_title' => 'Sharing Information',
    'privacy_s3_body' => 'Lima América Tours does not sell, rent or commercialize your personal information to third parties. We only share data with essential operational providers (the PayPal payment platform when you choose to pay immediately, transactional email sending service) who are contractually committed to treating the data with the same level of protection. We may disclose information when required by law or to protect our legal rights.',

    'privacy_s4_title' => 'Cookies',
    'privacy_s4_body' => 'We use first-party cookies for shopping cart functionality and session management, as well as third-party cookies from Google Analytics (anonymous) for traffic analysis. You can configure your browser to reject cookies; please note that this may affect site functionality. By continuing to browse you accept our cookie policy.',

    'privacy_s5_title' => 'User Rights',
    'privacy_s5_body' => 'In accordance with Peru\'s Personal Data Protection Law No. 29733, you have the right to access, rectify, cancel or oppose the processing of your personal data.',
    // The "Jr. Lampa 209, Lima Center" mention was removed 2026-08-11: not a
    // confirmed address (see App\Models\Setting::contactAddress()).
    'privacy_s5_exercise_email' => 'To exercise these rights, send a written request to our email address.',
    'privacy_s5_exercise_email_and_address' => 'To exercise these rights, send a written request to our email address or physical address (:address).',
    'privacy_s5_response_time' => 'We will respond within a maximum of 20 business days.',

    // ── ESNNA — code of conduct (see the note in lang/es/legal.php) ────────
    'esnna_last_updated' => 'Last updated: August 24, 2026',
    'esnna_title' => 'Code of conduct against ESNNA',
    'esnna_intro' => 'Lima América Tours absolutely rejects the sexual exploitation of children and adolescents (ESNNA) in tourism, and publicly commits to preventing and reporting it.',

    'esnna_poster_alt' => 'Official MINCETUR poster: this agency does not promote or permit the sexual exploitation of children and adolescents, in accordance with Peruvian Law No. 29408. Report it free of charge on 1818 or Línea 100.',
    'esnna_poster_caption' => 'Official poster of the Peruvian Ministry of Foreign Trade and Tourism. Click to view it full size.',

    'esnna_s1_title' => 'Our commitment',
    'esnna_s1_body' => 'As a Peruvian travel agency, we take it as given that tourism must never become a route to abuse. We commit to never facilitate, promote, tolerate or cover up any form of sexual exploitation of children and adolescents, neither directly nor through third parties working with us. This commitment binds our whole staff, our guides and every transport, lodging and activity provider we operate with.',

    'esnna_s2_title' => 'What ESNNA is',
    'esnna_s2_body' => 'The sexual exploitation of children and adolescents is any situation in which a person under 18 is used for sexual activity in exchange for money, goods, favours or any other advantage, whether for the exploiter or for a third party. It is not work, it is not a choice, and it does not stop being a crime because there was a payment, a middleman, or the apparent consent of the victim or their family.',

    'esnna_s3_title' => 'What we do in practice',
    'esnna_s3_body' => 'We brief and train our team to recognise warning signs; we include this commitment in the agreements with our providers; we refuse any request for services that could be aimed at exploiting minors, cancelling the booking with no refund; and we report to the competent authorities any fact or indication we detect, protecting the identity of the victim and of whoever reports it.',

    'esnna_s4_title' => 'How to report',
    'esnna_s4_intro' => 'If you know of or suspect a case, reporting is free, can be anonymous and needs no evidence: reasonable suspicion is enough.',
    'esnna_s4_line100' => 'Línea 100 (Ministry of Women and Vulnerable Populations): dial 100, free, 24 hours a day, from any phone in Peru.',
    'esnna_s4_police' => 'Peruvian National Police: dial 105 in an emergency, or go to the nearest police station.',
    'esnna_s4_us' => 'You can also write to us: if what you saw happened during one of our tours or involved one of our providers, we want to know so we can act and report it.',

    'esnna_s5_title' => 'Legal framework',
    'esnna_s5_body' => 'In Peru, the commercial sexual exploitation of children and adolescents in tourism is a crime under the Penal Code following Law No. 28251. Law No. 29408, the General Tourism Law, requires tourism service providers to help prevent it. This code of conduct is how we apply those obligations, and it is reviewed whenever the regulations change.',
];
