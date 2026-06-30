<?php
/**
 * Migration 035: Hand-written SEO titles + meta descriptions (top 20 beaches)
 *
 * These are bespoke, hand-authored title tags and meta descriptions (English +
 * Spanish) for the 20 highest-impression / lowest-CTR beach pages identified in
 * Search Console. They are LITERAL authored content (not generated), applied to
 * the seo_title / seo_title_es / seo_description / seo_description_es columns
 * added in migration 034. Titles are sized to render fully in the SERP (no brand
 * suffix is appended for overridden pages). Idempotent — re-running re-applies
 * the same values.
 *
 * Each title/description is factual to that beach's real tags, amenities and
 * municipality. Two beaches without a Google rating (las-picuas, coco-beach-
 * rio-grande) are described without rating-dependent claims.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: hand-written SEO content for top 20 beaches\n";

$db = getDb();

// slug => [seo_title (EN), seo_title_es (ES), seo_description (EN), seo_description_es (ES)]
$content = [
    'playa-de-vega' => [
        'Playa de Vega, Vega Baja — Calm Water & Snorkel',
        'Playa de Vega Baja — Aguas Tranquilas y Snorkel',
        'A calm, family-friendly beach in Vega Baja with snorkeling, lifeguards, parking, food and restrooms. See live conditions, photos and directions.',
        'Playa tranquila y familiar en Vega Baja con snorkel, salvavidas, estacionamiento y kioscos. Mira las condiciones, fotos y cómo llegar.',
    ],
    'playa-la-jungla' => [
        'Playa La Jungla, Guánica — Snorkel & Calm Water',
        'Playa La Jungla, Guánica — Snorkel y Aguas Tranquilas',
        'Snorkel the calm, clear water at Playa La Jungla in Guánica. Family-friendly with parking and water sports — see conditions, photos and directions.',
        'Haz snorkel en las aguas tranquilas y cristalinas de Playa La Jungla, Guánica. Ideal para familias, con estacionamiento. Mira fotos y cómo llegar.',
    ],
    'hobie-beach' => [
        'Hobie Beach, Carolina — Watersports, Rentals & Food',
        'Playa Hobie, Carolina — Deportes Acuáticos y Kioscos',
        'Hobie Beach in Carolina\'s Isla Verde: calm water for watersports, with equipment rentals, food kiosks, shade and parking. See photos and directions.',
        'Playa Hobie en Isla Verde, Carolina: aguas para deportes acuáticos, alquiler de equipo, kioscos, sombra y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'balneario-de-carolina' => [
        'Balneario de Carolina — Public Beach with Lifeguards',
        'Balneario de Carolina — Playa Pública con Salvavidas',
        'Carolina\'s public balneario by Isla Verde: lifeguards, ample parking, restrooms and showers on a wide sandy beach. See hours, conditions and directions.',
        'El balneario público de Carolina, junto a Isla Verde: salvavidas, amplio estacionamiento, baños y duchas. Mira horario, condiciones y cómo llegar.',
    ],
    'balneario-cerro-gordo' => [
        'Balneario Cerro Gordo, Vega Alta — Swim & Camping',
        'Balneario Cerro Gordo, Vega Alta — Nadar y Acampar',
        'Calm swimming, camping and picnic areas at Balneario Cerro Gordo, Vega Alta — lifeguards, parking, restrooms and showers. See conditions and directions.',
        'Aguas tranquilas para nadar, acampar y áreas de picnic en Balneario Cerro Gordo, Vega Alta: salvavidas, estacionamiento, baños y duchas. Mira cómo llegar.',
    ],
    'balneario-isla-verde' => [
        'Balneario Isla Verde, Carolina — Calm Swim & Lifeguards',
        'Balneario Isla Verde, Carolina — Aguas Tranquilas',
        'Calm, family-friendly swimming at Balneario Isla Verde in Carolina, with lifeguards, food, picnic areas, parking and showers. See photos and directions.',
        'Aguas tranquilas y familiares en el Balneario de Isla Verde, Carolina: salvavidas, kioscos, áreas de picnic y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'ojo-de-agua-beach' => [
        'Ojo de Agua Beach, Vega Baja — Calm Water & Parking',
        'Ojo de Agua, Vega Baja — Aguas Tranquilas para Nadar',
        'Ojo de Agua in Vega Baja: calm, shallow water perfect for families, with food, shade and parking. See live conditions, photos and how to get there.',
        'Ojo de Agua en Vega Baja: aguas tranquilas y poco profundas, ideales para familias, con kioscos, sombra y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'las-picuas' => [
        'Las Picúas Beach, Río Grande — Scenic Coast & Parking',
        'Playa Las Picúas, Río Grande — Costa Escénica',
        'Las Picúas in Río Grande — a scenic, low-key stretch of coast with parking nearby. See photos, map and how to get there.',
        'Las Picúas en Río Grande: un tramo de costa escénico y tranquilo con estacionamiento cercano. Mira fotos, mapa y cómo llegar.',
    ],
    'balneario-cana-gorda' => [
        'Balneario Caña Gorda, Guánica — Public Beach & Parking',
        'Balneario Caña Gorda, Guánica — Playa Pública',
        'Caña Gorda public beach in Guánica: calm Caribbean water, palm-lined sand, parking, restrooms and showers. See conditions, photos and directions.',
        'Balneario Caña Gorda en Guánica: aguas tranquilas del Caribe, arena con palmeras, estacionamiento, baños y duchas. Mira condiciones y cómo llegar.',
    ],
    'seven-seas-beach' => [
        'Seven Seas Beach, Fajardo — Camping & Lifeguards',
        'Seven Seas, Fajardo — Acampar, Salvavidas y Baños',
        'Seven Seas in Fajardo: calm, protected water for swimming and camping, with lifeguards, parking and restrooms. See conditions, photos and directions.',
        'Seven Seas en Fajardo: aguas tranquilas y protegidas para nadar y acampar, con salvavidas, estacionamiento y baños. Mira condiciones y cómo llegar.',
    ],
    'balneario-publico-de-boqueron' => [
        'Balneario de Boquerón, Cabo Rojo — Calm Swim & Parking',
        'Balneario de Boquerón, Cabo Rojo — Aguas Tranquilas',
        'Boquerón\'s public balneario in Cabo Rojo: calm, swimmable water, food, picnic areas, parking and restrooms. See conditions, photos and directions.',
        'El balneario público de Boquerón, Cabo Rojo: aguas tranquilas para nadar, kioscos, áreas de picnic, estacionamiento y baños. Mira fotos y cómo llegar.',
    ],
    'playa-santa' => [
        'Playa Santa, Guánica — Calm Water, Watersports & Food',
        'Playa Santa, Guánica — Aguas Tranquilas y Kioscos',
        'Playa Santa, Guánica: calm shallow water with watersport rentals, food kiosks and parking. Great for families — see photos, conditions and directions.',
        'Playa Santa en Guánica: aguas tranquilas y poco profundas, alquiler de deportes acuáticos, kioscos y estacionamiento. Ideal para familias. Mira fotos.',
    ],
    'coco-beach-rio-grande' => [
        'Coco Beach, Río Grande — Wide Sand by the Resorts',
        'Coco Beach, Río Grande — Arena Amplia junto a Resorts',
        'Coco Beach in Río Grande — a wide, scenic stretch of sand near the resort coast, with parking. See photos, map and how to get there.',
        'Coco Beach en Río Grande: un tramo amplio y escénico de arena cerca de la costa de resorts, con estacionamiento. Mira fotos, mapa y cómo llegar.',
    ],
    'montones-beach' => [
        'Montones Beach, Isabela — Tide Pools & Snorkel',
        'Playa Montones, Isabela — Piscinas Naturales y Snorkel',
        'Montones in Isabela — protected tide pools and reef ideal for kids and snorkeling, with parking. See live conditions, photos and how to get there.',
        'Playa Montones en Isabela: piscinas naturales y arrecife ideales para niños y snorkel, con estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'black-eagle-beach' => [
        'Black Eagle Beach, Rincón — Snorkel, Dive & Fish',
        'Black Eagle Beach, Rincón — Snorkel, Buceo y Pesca',
        'Black Eagle Beach in Rincón — clear water for snorkeling, diving and fishing, with rentals and parking. See conditions, photos and how to get there.',
        'Black Eagle Beach en Rincón: aguas claras para snorkel, buceo y pesca, con alquiler de equipo y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'isla-verde-beach' => [
        'Isla Verde Beach, Carolina — Swim, Surf & Food',
        'Playa Isla Verde, Carolina — Nadar, Surf y Kioscos',
        'Isla Verde Beach, Carolina: golden sand for swimming and surf, with lifeguards, rentals, food and parking. See conditions, photos and directions.',
        'Playa Isla Verde, Carolina: arena dorada para nadar y surfear, con salvavidas, kioscos y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'playa-dona-lala-beach' => [
        'Playa Doña Lala — Family Beach with Surf & Shade',
        'Playa Doña Lala — Playa Familiar con Surf y Sombra',
        'Playa Doña Lala — a calm, family-friendly beach on the west coast with gentle surf, food, shade and parking. See conditions, photos and directions.',
        'Playa Doña Lala: una playa familiar y tranquila en la costa oeste con surf suave, kioscos, sombra y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'cayo-matias-salinas' => [
        'Cayo Matías, Salinas — Secluded Cay & Picnic Spot',
        'Cayo Matías, Salinas — Cayo Apartado para Pícnic',
        'Cayo Matías off Salinas — a secluded, scenic cay reached by boat, with calm water and picnic areas. See photos, map and how to get there.',
        'Cayo Matías frente a Salinas: un cayo apartado y escénico al que se llega en bote, con aguas tranquilas y áreas de pícnic. Mira fotos y cómo llegar.',
    ],
    'muelle-de-azucar-beach' => [
        'Muelle de Azúcar, Aguadilla — Calm Water & Snorkel',
        'Muelle de Azúcar, Aguadilla — Aguas Tranquilas y Snorkel',
        'Muelle de Azúcar in Aguadilla — calm, clear water for swimming, snorkeling and fishing, with shade and parking. See conditions, photos and directions.',
        'Muelle de Azúcar en Aguadilla: aguas tranquilas y claras para nadar, snorkel y pesca, con sombra y estacionamiento. Mira condiciones y cómo llegar.',
    ],
    'punta-bandera-luquillo-pr' => [
        'Punta Bandera, Luquillo — Calm, Secluded Swimming',
        'Punta Bandera, Luquillo — Aguas Tranquilas y Apartada',
        'Punta Bandera in Luquillo — a calm, secluded beach for swimming, with natural shade. See live conditions, photos and how to get there.',
        'Punta Bandera en Luquillo: una playa tranquila y apartada para nadar, con sombra natural. Mira condiciones, fotos y cómo llegar.',
    ],
];

$stmt = $db->prepare(
    'UPDATE beaches SET seo_title = :t, seo_title_es = :tes, seo_description = :d, seo_description_es = :des
     WHERE slug = :slug'
);

$applied = 0;
$missing = [];
foreach ($content as $slug => $vals) {
    $exists = (int) $db->querySingle("SELECT COUNT(*) FROM beaches WHERE slug = '" . SQLite3::escapeString($slug) . "'");
    if ($exists === 0) {
        $missing[] = $slug;
        continue;
    }
    $stmt->reset();
    $stmt->bindValue(':t', $vals[0], SQLITE3_TEXT);
    $stmt->bindValue(':tes', $vals[1], SQLITE3_TEXT);
    $stmt->bindValue(':d', $vals[2], SQLITE3_TEXT);
    $stmt->bindValue(':des', $vals[3], SQLITE3_TEXT);
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $stmt->execute();
    $applied++;
}

echo "Applied SEO overrides to {$applied} beaches.\n";
if ($missing) {
    echo "WARNING: " . count($missing) . " target slug(s) not found: " . implode(', ', $missing) . "\n";
}
