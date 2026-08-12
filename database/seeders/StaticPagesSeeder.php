<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Crea los registros `Page` de "nosotros" y "contacto" que PageResource
 * necesita para poder editarlos (pestaña "Contenido de la página").
 *
 * Hallazgo CRO 2026-08-11 (lote agosto-2026): se migró un montón de copy de
 * ambas páginas a Filament (PageResource → tab "Contenido de la página"),
 * pero SIN estos dos registros el listado de Páginas del panel aparece
 * vacío y el cliente no tiene nada que editar, aunque toda la UI ya esté
 * construida. El CRO tuvo que crear los registros a mano para poder probar
 * el fix y después los borró.
 *
 * `firstOrCreate` es a propósito: si el registro YA existe (porque el
 * cliente ya lo editó desde el panel), este seeder no toca nada. Solo crea
 * lo que falta, nunca pisa contenido editado.
 *
 * Los valores de `blocks` de abajo son una COPIA EXACTA del texto que hoy
 * sale por defecto en `resources/views/about.blade.php` y
 * `resources/views/contact.blade.php` cuando `$page` es null o
 * `$page->blocks` no trae la clave (ver los helpers `$bl()`/`$L()` de esos
 * blades). Se copian tal cual para que, al abrir el panel, el cliente vea
 * el texto REAL que está publicado ahora mismo — y lo edite encima, en vez
 * de encontrar campos vacíos sin saber qué se está mostrando. Si cambias el
 * copy por defecto en esos blades, actualiza también este seeder para que
 * una instalación nueva siga mostrando lo mismo.
 *
 * OJO: NO se incluyen aquí los campos de `blocks` que existen en el
 * formulario de PageResource pero que ningún blade lee todavía
 * (`why_intro`, `hero_cta_label`, `why_intro2`/`why_cta_label`,
 * `banner_heading`/`banner_text`, `cultura_heading`/`cultura_intro`,
 * `pillars`, `testimonios_eyebrow`/`testimonios_heading` en "nosotros") ni
 * los campos que SÍ lee el blade pero que no tienen field en el formulario
 * (`split_eyebrow`, `split_heading`, `split_body_1`, `split_body_2`,
 * `split_highlight`, `img_split` en "nosotros"). Inventarles contenido acá
 * sería publicar un dato que hoy no es ni editable ni realmente una
 * "copia de lo publicado" en un caso, o data muerta en el otro. Pendiente
 * de un dato/decisión aparte, no de este seeder.
 *
 * Tampoco se puebla `blocks.stats` (Misión/Visión/Valores): el repeater de
 * PageResource solo soporta título+descripción por item, pero el default
 * real de la 3ª tarjeta ("Valores") son 8 chips (`tags`), no una
 * descripción — el repeater no puede representarlo sin cambiar lo que se
 * ve. Se deja sin sembrar para no alterar el render (about.blade.php cae a
 * $defaultMvv, que sí trae los 8 valores como chips).
 */
class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'contacto'],
            [
                'title_es' => 'Contáctanos',
                'title_en' => 'Contact Us',
                'title_pt' => 'Fale Conosco',
                'content_es' => null,
                'content_en' => null,
                'content_pt' => null,
                'blocks' => [
                    // Hero
                    'hero_eyebrow_es' => 'ESTAMOS PARA AYUDARTE',
                    'hero_eyebrow_en' => 'WE ARE HERE TO HELP',
                    'hero_eyebrow_pt' => 'ESTAMOS AQUI PARA AJUDAR',
                    'hero_title_es' => 'Contáctanos',
                    'hero_title_en' => 'Contact us',
                    'hero_title_pt' => 'Fale conosco',
                    'hero_lead_es' => '¿Tienes dudas o quieres armar un tour a tu medida? Escríbenos y te respondemos a la brevedad.',
                    'hero_lead_en' => 'Have questions or want a custom-made tour? Write to us and we will get back to you shortly.',
                    'hero_lead_pt' => 'Tem dúvidas ou quer montar um tour sob medida? Escreva para nós e responderemos em breve.',
                    'hero_image_alt_es' => 'Faro de Miraflores y malecón de Lima al atardecer',
                    'hero_image_alt_en' => "Miraflores lighthouse and Lima's coastal boardwalk at sunset",
                    'hero_image_alt_pt' => 'Farol de Miraflores e orla de Lima ao entardecer',

                    // 3 chips de confianza del hero
                    'chip1_title_es' => 'Respuesta rápida',
                    'chip1_title_en' => 'Fast response',
                    'chip1_title_pt' => 'Resposta rápida',
                    'chip1_desc_es' => 'Te respondemos a la brevedad',
                    'chip1_desc_en' => 'We reply to you shortly',
                    'chip1_desc_pt' => 'Respondemos rapidamente',
                    'chip2_title_es' => 'Atención personalizada',
                    'chip2_title_en' => 'Personalized service',
                    'chip2_title_pt' => 'Atendimento personalizado',
                    'chip2_desc_es' => 'Te ayudamos a crear la mejor experiencia',
                    'chip2_desc_en' => 'We help you build the best experience',
                    'chip2_desc_pt' => 'Ajudamos você a criar a melhor experiência',
                    'chip3_title_es' => 'Viaja con confianza',
                    'chip3_title_en' => 'Travel with confidence',
                    'chip3_title_pt' => 'Viaje com confiança',
                    'chip3_desc_es' => 'Seguridad y respaldo garantizado',
                    'chip3_desc_en' => 'Guaranteed safety and support',
                    'chip3_desc_pt' => 'Segurança e suporte garantidos',

                    // Formulario — cabecera y placeholders
                    'form_title_es' => 'Envíanos un mensaje',
                    'form_title_en' => 'Send us a message',
                    'form_title_pt' => 'Envie-nos uma mensagem',
                    'form_desc_es' => 'Completa el formulario y te contactamos a la brevedad.',
                    'form_desc_en' => 'Fill out the form and we will get back to you shortly.',
                    'form_desc_pt' => 'Preencha o formulário e entraremos em contato em breve.',
                    'ph_nombre_es' => 'Ej. María López',
                    'ph_nombre_en' => 'E.g. Maria Lopez',
                    'ph_nombre_pt' => 'Ex. Maria Lopez',
                    'ph_asunto_es' => '¿En qué podemos ayudarte?',
                    'ph_asunto_en' => 'How can we help?',
                    'ph_asunto_pt' => 'Como podemos ajudar?',
                    'ph_mensaje_es' => 'Cuéntanos tu plan de viaje, fechas, número de personas, intereses, etc.',
                    'ph_mensaje_en' => 'Tell us about your trip plan, dates, number of people, interests, etc.',
                    'ph_mensaje_pt' => 'Conte-nos seu plano de viagem, datas, número de pessoas, interesses, etc.',

                    // Canales de contacto — etiquetas (el dato sale de Configuración → Contacto)
                    'channel_phone_label_es' => 'Teléfono / WhatsApp',
                    'channel_phone_label_en' => 'Phone / WhatsApp',
                    'channel_phone_label_pt' => 'Telefone / WhatsApp',
                    'channel_email_label_es' => 'Correo',
                    'channel_email_label_en' => 'Email',
                    'channel_email_label_pt' => 'E-mail',
                    'channel_hours_label_es' => 'Horario de atención',
                    'channel_hours_label_en' => 'Business hours',
                    'channel_hours_label_pt' => 'Horário de atendimento',
                    'channel_pickup_label_es' => 'Punto de recojo',
                    'channel_pickup_label_en' => 'Pickup point',
                    'channel_pickup_label_pt' => 'Ponto de encontro',
                    'channel_pickup_note_es' => 'Coordinamos el punto de recojo o encuentro al confirmar tu reserva, según el tour y tu ubicación en Lima.',
                    'channel_pickup_note_en' => 'We coordinate the pickup or meeting point once your booking is confirmed, based on the tour and your location in Lima.',
                    'channel_pickup_note_pt' => 'Coordenamos o ponto de encontro ao confirmar sua reserva, de acordo com o tour e sua localização em Lima.',

                    // Card de asesor
                    'advisor_title_es' => '¿Necesitas ayuda para elegir tu tour?',
                    'advisor_title_en' => 'Need help choosing your tour?',
                    'advisor_title_pt' => 'Precisa de ajuda para escolher seu tour?',
                    'advisor_desc_es' => 'Nuestros asesores te ayudarán a crear una experiencia a tu medida.',
                    'advisor_desc_en' => 'Our advisors will help you create a tailor-made experience.',
                    'advisor_desc_pt' => 'Nossos consultores vão ajudar você a criar uma experiência sob medida.',
                    'advisor_cta_es' => 'Hablar con un asesor',
                    'advisor_cta_en' => 'Talk to an advisor',
                    'advisor_cta_pt' => 'Falar com um consultor',

                    // Franja inferior "¿Listo para tu próxima aventura?"
                    'bottom_title_es' => '¿Listo para tu próxima aventura?',
                    'bottom_title_en' => 'Ready for your next adventure?',
                    'bottom_title_pt' => 'Pronto para sua próxima aventura?',
                    'bottom_desc_es' => 'Escríbenos y armamos juntos la experiencia perfecta para ti.',
                    'bottom_desc_en' => 'Write to us and let’s build the perfect experience together.',
                    'bottom_desc_pt' => 'Escreva para nós e vamos montar juntos a experiência perfeita para você.',
                    'bottom_cta_es' => 'Ver tours populares',
                    'bottom_cta_en' => 'See popular tours',
                    'bottom_cta_pt' => 'Ver tours populares',
                ],
                'is_published' => true,
                // false a propósito: /contacto ya está en el sitemap como ruta
                // estática (SitemapController::index, $staticRoutes). Si este
                // registro entrara con show_in_sitemap=true, la sección "CMS
                // pages" del mismo controller agregaría la URL POR SEGUNDA VEZ
                // (un <url> duplicado por idioma en sitemap.xml).
                'show_in_sitemap' => false,
                'sitemap_priority' => '0.6',
                'sitemap_changefreq' => 'monthly',
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'nosotros'],
            [
                'title_es' => 'Nosotros',
                'title_en' => 'About Us',
                'title_pt' => 'Sobre Nós',
                'content_es' => null,
                'content_en' => null,
                'content_pt' => null,
                'blocks' => [
                    // Hero
                    'hero_eyebrow_es' => 'Nuestra historia',
                    'hero_eyebrow_en' => 'Our story',
                    'hero_eyebrow_pt' => 'Nossa história',
                    'hero_title_es' => 'Más de 10 años mostrando lo mejor del Perú',
                    'hero_title_en' => 'More than 10 years showcasing the best of Peru',
                    'hero_title_pt' => 'Mais de 10 anos mostrando o melhor do Peru',
                    'hero_lead_es' => 'En Lima América Tours compartimos nuestra pasión por el Perú a través de experiencias auténticas, memorables y llenas de cultura. No eres un turista, eres nuestro invitado.',
                    'hero_lead_en' => 'At Lima América Tours we share our passion for Peru through authentic, memorable experiences full of culture. You are not a tourist, you are our guest.',
                    'hero_lead_pt' => 'Na Lima América Tours compartilhamos nossa paixão pelo Peru por meio de experiências autênticas, memoráveis e cheias de cultura. Você não é um turista, é nosso convidado.',

                    // Fila de confianza (banda "Miles de viajeros...")
                    'trust_label_google_es' => 'Reseñas de Google',
                    'trust_label_google_en' => 'Google Reviews',
                    'trust_label_google_pt' => 'Avaliações no Google',
                    'trust_label_tripadvisor_es' => 'Presencia en Tripadvisor',
                    'trust_label_tripadvisor_en' => 'Present on Tripadvisor',
                    'trust_label_tripadvisor_pt' => 'Presença no Tripadvisor',
                    'trust_label_company_es' => 'Empresa registrada',
                    'trust_label_company_en' => 'Registered company',
                    'trust_label_company_pt' => 'Empresa registrada',

                    // CTA final — 4 features
                    'cta_feat1_fallback_es' => 'Escríbenos cuando quieras',
                    'cta_feat1_fallback_en' => 'Message us anytime',
                    'cta_feat1_fallback_pt' => 'Escreva quando quiser',
                    'cta_feat1_label_es' => 'Horario de atención',
                    'cta_feat1_label_en' => 'Support hours',
                    'cta_feat1_label_pt' => 'Horário de atendimento',
                    'cta_feat2_title_es' => 'Viajes 100% personalizados',
                    'cta_feat2_title_en' => '100% personalized trips',
                    'cta_feat2_title_pt' => 'Viagens 100% personalizadas',
                    'cta_feat2_desc_es' => 'Hechos a tu medida',
                    'cta_feat2_desc_en' => 'Made just for you',
                    'cta_feat2_desc_pt' => 'Feitas sob medida',
                    'cta_feat3_title_es' => 'Seguridad y confianza',
                    'cta_feat3_title_en' => 'Security and trust',
                    'cta_feat3_title_pt' => 'Segurança e confiança',
                    'cta_feat3_desc_es' => 'Tu tranquilidad es lo primero',
                    'cta_feat3_desc_en' => 'Your peace of mind comes first',
                    'cta_feat3_desc_pt' => 'Sua tranquilidade em primeiro lugar',
                    'cta_feat4_title_es' => 'Cancelación flexible',
                    'cta_feat4_title_en' => 'Flexible cancellation',
                    'cta_feat4_title_pt' => 'Cancelamento flexível',
                    'cta_feat4_desc_es' => 'Cambia tus planes sin complicaciones',
                    'cta_feat4_desc_en' => 'Change your plans hassle-free',
                    'cta_feat4_desc_pt' => 'Mude seus planos sem complicações',

                    // Checks de la card "¿Por qué viajar con Lima América Tours?"
                    // (repeater — sí se puebla: su schema real, texto simple,
                    // representa el default 1:1, a diferencia de "stats" más abajo).
                    'why_travel_items' => [
                        ['text_es' => 'Guías certificados', 'text_en' => 'Certified guides', 'text_pt' => 'Guias certificados'],
                        ['text_es' => 'Experiencias auténticas', 'text_en' => 'Authentic experiences', 'text_pt' => 'Experiências autênticas'],
                        ['text_es' => 'Grupos pequeños', 'text_en' => 'Small groups', 'text_pt' => 'Grupos pequenos'],
                        ['text_es' => 'Atención personalizada', 'text_en' => 'Personalized support', 'text_pt' => 'Atendimento personalizado'],
                        ['text_es' => 'Cancelación flexible', 'text_en' => 'Flexible cancellation', 'text_pt' => 'Cancelamento flexível'],
                    ],
                ],
                'is_published' => true,
                // false por el mismo motivo que "contacto": /nosotros ya está
                // en el sitemap como ruta estática — evita el <url> duplicado.
                'show_in_sitemap' => false,
                'sitemap_priority' => '0.6',
                'sitemap_changefreq' => 'monthly',
            ]
        );
    }
}
