<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Seed 4 real blog posts for Lima América Tours, matching the
 * "Post Recientes" mockup (lat-05-blog.jpeg). Covers reuse existing
 * tour photography already present in storage/app/public/tours so
 * the blog grid renders with real, on-brand imagery.
 */
class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'como-se-prepara-el-ceviche-peruano',
                'category' => 'Gastronomía',
                'cover_image' => 'tours/CITY-TOUR-LIMA-CLASES-DE-PISCO-SOUR-3.jpg',
                'author_name' => 'Lima América Tours',
                'published_at' => now()->subDays(6),
                'title_es' => 'Cómo se prepara el ceviche peruano',
                'title_en' => 'How Peruvian ceviche is made',
                'title_pt' => 'Como se prepara o ceviche peruano',
                'excerpt_es' => 'El ceviche es el plato bandera del Perú: pescado fresco, limón, ají y cebolla se combinan en una receta simple que enamora a cualquier viajero.',
                'excerpt_en' => 'Ceviche is Peru\'s flagship dish: fresh fish, lime, chili and onion combine in a simple recipe that wins over every traveler.',
                'excerpt_pt' => 'O ceviche é o prato bandeira do Peru: peixe fresco, limão, pimenta e cebola se combinam em uma receita simples que conquista qualquer viajante.',
                'body_es' => "<p>El ceviche peruano es mucho más que una receta: es una tradición que resume la identidad culinaria del país. Su preparación parte de un pescado blanco muy fresco, cortado en cubos y \"cocinado\" en jugo de limón recién exprimido.</p><p>Se acompaña con cebolla roja cortada en pluma, ají limo picado y un toque de cilantro. El resultado se sirve frío, con camote, choclo y cancha serrana como guarnición tradicional.</p><p>En nuestros tours gastronómicos por Lima llevamos a nuestros viajeros a probar el ceviche directamente de la mano de cocineros locales, en mercados y cevicherías con historia.</p>",
                'body_en' => "<p>Peruvian ceviche is much more than a recipe: it's a tradition that sums up the country's culinary identity. It starts with very fresh white fish, cut into cubes and \"cooked\" in freshly squeezed lime juice.</p><p>It's served with thinly sliced red onion, chopped ají limo chili and a touch of cilantro, alongside sweet potato, corn and toasted Andean corn (cancha).</p><p>On our gastronomic tours around Lima we take travelers to taste ceviche straight from local cooks, in markets and historic cevicherías.</p>",
                'body_pt' => "<p>O ceviche peruano é muito mais que uma receita: é uma tradição que resume a identidade culinária do país. Sua preparação parte de um peixe branco bem fresco, cortado em cubos e \"cozido\" em suco de limão espremido na hora.</p><p>É acompanhado de cebola roxa fatiada, pimenta ají limo picada e um toque de coentro, servido com batata-doce, milho e cancha serrana.</p><p>Em nossos tours gastronômicos por Lima levamos nossos viajantes a provar o ceviche direto das mãos de cozinheiros locais.</p>",
            ],
            [
                'slug' => 'huacachina-el-oasis-imperdible-de-ica',
                'category' => 'Destinos',
                'cover_image' => 'tours/OASIS-DE-HUACACHINA-CON-BUGGIE-10-scaled-1.jpg',
                'author_name' => 'Lima América Tours',
                'published_at' => now()->subDays(4),
                'title_es' => 'Huacachina: el oasis imperdible de Ica',
                'title_en' => 'Huacachina: the must-see oasis of Ica',
                'title_pt' => 'Huacachina: o oásis imperdível de Ica',
                'excerpt_es' => 'Rodeado de dunas gigantes, este pequeño oasis a las afueras de Ica es el escenario perfecto para el sandboarding y los paseos en buggy al atardecer.',
                'excerpt_en' => 'Surrounded by giant dunes, this small oasis just outside Ica is the perfect setting for sandboarding and sunset buggy rides.',
                'excerpt_pt' => 'Cercado por dunas gigantes, este pequeno oásis nos arredores de Ica é o cenário perfeito para sandboard e passeios de buggy ao entardecer.',
                'body_es' => "<p>Huacachina es una laguna natural rodeada de palmeras en medio de un desierto de dunas que puede superar los 100 metros de altura. Está a solo unos minutos del centro de la ciudad de Ica.</p><p>La experiencia más popular es el paseo en buggy: un vehículo todoterreno que sube y baja las dunas a gran velocidad, seguido de una sesión de sandboarding para deslizarse sobre la arena.</p><p>El mejor momento para visitarla es al atardecer, cuando la luz dorada baña las dunas y el cielo se pinta de tonos naranjas y rosados.</p>",
                'body_en' => "<p>Huacachina is a natural lagoon surrounded by palm trees in the middle of a desert of dunes that can exceed 100 meters in height, just minutes from downtown Ica.</p><p>The most popular experience is the buggy ride: an off-road vehicle that climbs and descends the dunes at high speed, followed by a sandboarding session down the slopes.</p><p>The best time to visit is at sunset, when golden light bathes the dunes and the sky turns shades of orange and pink.</p>",
                'body_pt' => "<p>Huacachina é uma lagoa natural cercada de palmeiras em meio a um deserto de dunas que pode superar 100 metros de altura, a poucos minutos do centro de Ica.</p><p>A experiência mais popular é o passeio de buggy, seguido de uma sessão de sandboard para deslizar pela areia.</p><p>O melhor momento para visitar é ao entardecer, quando a luz dourada banha as dunas.</p>",
            ],
            [
                'slug' => 'free-tours-en-lima-la-mejor-forma-de-conocer-la-ciudad',
                'category' => 'Tours',
                'cover_image' => 'tours/2017_Lima_-_Escultura_El_beso_en_el_Parque_del_Amor.jpg',
                'author_name' => 'Lima América Tours',
                'published_at' => now()->subDays(2),
                'title_es' => 'Free Tours en Lima: la mejor forma de conocer la ciudad',
                'title_en' => 'Free Tours in Lima: the best way to see the city',
                'title_pt' => 'Free Tours em Lima: a melhor forma de conhecer a cidade',
                'excerpt_es' => 'Camina por el Centro Histórico, Miraflores o Barranco junto a un guía local y descubre la historia de Lima sin costo fijo: solo pagas la propina que consideres justa.',
                'excerpt_en' => 'Walk through the Historic Center, Miraflores or Barranco with a local guide and discover Lima\'s history at no fixed cost: you only pay the tip you think is fair.',
                'excerpt_pt' => 'Caminhe pelo Centro Histórico, Miraflores ou Barranco com um guia local e descubra a história de Lima sem custo fixo: você só paga a gorjeta que considerar justa.',
                'body_es' => "<p>Los free tours son caminatas guiadas por el centro histórico y los distritos turísticos de Lima, dictadas por guías locales apasionados por contar la historia de su ciudad.</p><p>El formato \"free\" no significa gratis: al final del recorrido cada viajero decide cuánto dar como propina, según su experiencia y posibilidades.</p><p>Es una excelente alternativa para quienes recién llegan a Lima y quieren orientarse, conocer gente y escuchar anécdotas que no aparecen en las guías tradicionales.</p>",
                'body_en' => "<p>Free tours are guided walks through Lima's historic center and touristic districts, led by local guides passionate about telling their city's story.</p><p>The \"free\" format doesn't mean it's free of charge: at the end of the tour, each traveler decides how much to tip based on their experience.</p><p>It's a great option for newcomers to Lima who want to get their bearings, meet people and hear stories you won't find in traditional guidebooks.</p>",
                'body_pt' => "<p>Os free tours são caminhadas guiadas pelo centro histórico e distritos turísticos de Lima, conduzidas por guias locais apaixonados por contar a história de sua cidade.</p><p>O formato \"free\" não significa gratuito: ao final do passeio cada viajante decide quanto dar de gorjeta.</p><p>É uma ótima alternativa para quem chega a Lima e quer se orientar e conhecer gente nova.</p>",
            ],
            [
                'slug' => 'que-hacer-en-barranco-y-miraflores',
                'category' => 'Guías Locales',
                'cover_image' => 'tours/Lima_-Everything-You-Need-to-Know-About-The-Capital-of-Peru.jpeg',
                'author_name' => 'Lima América Tours',
                'published_at' => now()->subDay(),
                'title_es' => '¿Qué hacer en Barranco y Miraflores?',
                'title_en' => 'What to do in Barranco and Miraflores?',
                'title_pt' => 'O que fazer em Barranco e Miraflores?',
                'excerpt_es' => 'Dos distritos vecinos, dos personalidades distintas: el malecón y los centros comerciales de Miraflores frente al arte urbano y los bares bohemios de Barranco.',
                'excerpt_en' => 'Two neighboring districts, two distinct personalities: Miraflores\' boardwalk and malls versus Barranco\'s street art and bohemian bars.',
                'excerpt_pt' => 'Dois bairros vizinhos, duas personalidades distintas: o calçadão e os shoppings de Miraflores contra a arte urbana e os bares boêmios de Barranco.',
                'body_es' => "<p>Miraflores es el distrito más turístico de Lima: cuenta con un malecón de más de 10 kilómetros junto al mar, el Parque del Amor, y una oferta gastronómica y comercial de primer nivel.</p><p>A pocos minutos, Barranco conserva casonas republicanas, puentes históricos como el Puente de los Suspiros, y una escena de arte urbano y vida nocturna bohemia única en la ciudad.</p><p>Recomendamos recorrer ambos distritos en un mismo día: mañana en Miraflores y tarde-noche en Barranco, para vivir el contraste completo de la Lima moderna.</p>",
                'body_en' => "<p>Miraflores is Lima's most touristic district: it has a boardwalk over 10 kilometers long by the sea, the Park of Love, and a top-tier dining and shopping scene.</p><p>Just minutes away, Barranco preserves republican-era mansions, historic bridges like the Bridge of Sighs, and a street art and bohemian nightlife scene unique to the city.</p><p>We recommend visiting both districts in the same day: morning in Miraflores and afternoon-evening in Barranco.</p>",
                'body_pt' => "<p>Miraflores é o bairro mais turístico de Lima: tem um calçadão de mais de 10 quilômetros à beira-mar, o Parque do Amor e uma oferta gastronômica de primeiro nível.</p><p>A poucos minutos, Barranco preserva casarões republicanos, pontes históricas e uma cena de arte urbana e vida noturna boêmia única na cidade.</p><p>Recomendamos visitar os dois bairros no mesmo dia.</p>",
            ],
        ];

        foreach ($posts as $data) {
            BlogPost::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_published' => true])
            );
        }
    }
}
