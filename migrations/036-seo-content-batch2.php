<?php
/**
 * Migration 036: Hand-written SEO titles + meta descriptions (next 28 beaches)
 *
 * Second batch of bespoke, hand-authored title tags and meta descriptions
 * (English + Spanish) for the next tier of underperforming beach pages from
 * Search Console (page-1 rank, <2% CTR), after the top-20 batch in migration
 * 035. Literal authored content (not generated), factual to each beach's tags,
 * amenities and municipality. Idempotent — re-running re-applies the same values.
 *
 * (playa-azul intentionally excluded — it is a likely duplicate of
 * playa-azul-luquillo and is flagged for a future merge rather than given a
 * competing optimized title.)
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: hand-written SEO content batch 2\n";

$db = getDb();

// slug => [seo_title (EN), seo_title_es (ES), seo_description (EN), seo_description_es (ES)]
$content = [
    'borinquen-beach' => [
        'Borinquen Beach, Aguadilla — Surf, Swim & Sunsets',
        'Playa Borinquen, Aguadilla — Surf, Nadar y Atardeceres',
        'Borinquen Beach at Punta Borinquen, Aguadilla — golden sand for surfing and swimming with great sunsets, shade and parking. See conditions and directions.',
        'Playa Borinquen en Punta Borinquen, Aguadilla: arena para surfear y nadar, con atardeceres, sombra y estacionamiento. Mira condiciones y cómo llegar.',
    ],
    'buye-beach' => [
        'Buyé Beach, Cabo Rojo — Calm Snorkel Water & Palms',
        'Playa Buyé, Cabo Rojo — Aguas Tranquilas y Snorkel',
        'Buyé Beach in Cabo Rojo: calm, clear water for swimming and snorkeling, palm-shaded sand, food, restrooms and parking. See conditions, photos and directions.',
        'Playa Buyé en Cabo Rojo: aguas tranquilas y claras para nadar y snorkel, arena con palmeras, kioscos, baños y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'mar-chiquita-village' => [
        'Mar Chiquita Beach, Manatí — Crescent Cove & Swim',
        'Mar Chiquita, Manatí — Cala en Forma de Media Luna',
        'Mar Chiquita in Manatí — a sheltered crescent cove with calm pockets for swimming, plus food and parking. See conditions, photos and how to get there.',
        'Mar Chiquita en Manatí: una cala protegida en forma de media luna con aguas tranquilas para nadar, kioscos y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'balneario-de-arroyo' => [
        'Balneario de Arroyo — Calm Swim, Camping & Lifeguards',
        'Balneario de Arroyo — Nadar, Acampar y Salvavidas',
        'Arroyo\'s public balneario: calm, accessible swimming with camping, lifeguards, picnic areas, restrooms and parking. See conditions, photos and directions.',
        'El balneario público de Arroyo: aguas tranquilas y accesibles para nadar, con camping, salvavidas, áreas de picnic, baños y estacionamiento. Mira cómo llegar.',
    ],
    'playa-de-fajardo' => [
        'Playa de Fajardo — Swimming, Fishing & Beach Food',
        'Playa de Fajardo — Nadar, Pesca y Kioscos',
        'Playa de Fajardo — a popular local beach for swimming and fishing, with food kiosks and parking nearby. See live conditions, photos and how to get there.',
        'Playa de Fajardo: una playa local popular para nadar y pescar, con kioscos y estacionamiento cercano. Mira condiciones, fotos y cómo llegar.',
    ],
    'pena-blanca-aguadilla' => [
        'Peña Blanca, Aguadilla — Secluded Snorkel Cove',
        'Peña Blanca, Aguadilla — Cala Apartada para Snorkel',
        'Peña Blanca in Aguadilla — a secluded, scenic cove with clear water for snorkeling and swimming, and parking nearby. See photos, conditions and directions.',
        'Peña Blanca en Aguadilla: una cala apartada y escénica con aguas claras para snorkel y nadar, y estacionamiento cercano. Mira fotos y cómo llegar.',
    ],
    'playa-ballena-guanica' => [
        'Playa Ballena, Guánica — Secluded Scenic Beach',
        'Playa Ballena, Guánica — Playa Apartada y Escénica',
        'Playa Ballena in Guánica — a secluded, scenic beach within the Bosque Seco dry forest, reached by trail. See photos, map and how to get there.',
        'Playa Ballena en Guánica: una playa apartada y escénica dentro del Bosque Seco, a la que se llega por sendero. Mira fotos, mapa y cómo llegar.',
    ],
    'playa-los-machos' => [
        'Playa Los Machos, Ceiba — Calm Family Beach',
        'Playa Los Machos, Ceiba — Playa Familiar y Tranquila',
        'Playa Los Machos in Ceiba — a calm, family-friendly beach with scenic views and parking. See live conditions, photos and how to get there.',
        'Playa Los Machos en Ceiba: una playa familiar y tranquila con vistas escénicas y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'balneario-de-boqueron' => [
        'Balneario de Boquerón, Cabo Rojo — Lifeguards & Sand',
        'Balneario de Boquerón, Cabo Rojo — Playa con Salvavidas',
        'Boquerón\'s balneario in Cabo Rojo — a long, calm sandy beach with lifeguards, parking, restrooms and showers. See conditions, photos and directions.',
        'El balneario de Boquerón, Cabo Rojo: una playa larga y tranquila con salvavidas, estacionamiento, baños y duchas. Mira condiciones, fotos y cómo llegar.',
    ],
    'poza-del-obispo' => [
        'Poza del Obispo, Arecibo — Reef Pool by the Lighthouse',
        'Poza del Obispo, Arecibo — Piscina de Arrecife',
        'Poza del Obispo in Arecibo — a protected reef pool beside the lighthouse, calm for families, with parking and restrooms. See conditions and directions.',
        'Poza del Obispo en Arecibo: una piscina natural protegida por arrecife junto al faro, tranquila para familias, con estacionamiento y baños. Mira cómo llegar.',
    ],
    'villa-pesquera-isabela' => [
        'Villa Pesquera, Isabela — Scenic Shore & Parking',
        'Villa Pesquera, Isabela — Costa Escénica',
        'Villa Pesquera in Isabela — a scenic fishing-village shore with parking and ocean views. See photos, map and how to get there.',
        'Villa Pesquera en Isabela: una costa escénica junto a la villa pesquera, con estacionamiento y vistas al mar. Mira fotos y cómo llegar.',
    ],
    'palmas-del-mar-beach' => [
        'Palmas del Mar Beach, Humacao — Swim & Watersports',
        'Playa Palmas del Mar, Humacao — Nadar y Deportes',
        'Palmas del Mar Beach in Humacao — calm resort-area water for swimming and watersports, with food, shade and parking. See conditions and directions.',
        'Playa Palmas del Mar en Humacao: aguas tranquilas para nadar y deportes acuáticos, con kioscos, sombra y estacionamiento. Mira condiciones y cómo llegar.',
    ],
    'pico-de-piedra' => [
        'Pico de Piedra, Aguada — Family Beach with Facilities',
        'Pico de Piedra, Aguada — Playa Familiar con Baños',
        'Pico de Piedra in Aguada — a family-friendly beach for swimming, with restrooms, showers and parking. See live conditions, photos and how to get there.',
        'Pico de Piedra en Aguada: una playa familiar para nadar, con baños, duchas y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'aguada-town-beach' => [
        'Aguada Town Beach — Scenic West-Coast Shore',
        'Playa de Aguada — Costa Escénica del Oeste',
        'Aguada\'s town beach — a scenic stretch of west-coast sand with parking nearby, close to the historic town center. See photos, map and how to get there.',
        'La playa del pueblo de Aguada: un tramo escénico de arena en la costa oeste con estacionamiento, cerca del casco del pueblo. Mira fotos y cómo llegar.',
    ],
    'playa-tamarindo' => [
        'Playa Tamarindo, Guánica — Secluded Snorkel Spot',
        'Playa Tamarindo, Guánica — Snorkel en Cala Apartada',
        'Playa Tamarindo in Guánica — a secluded, calm cove with clear water ideal for snorkeling and swimming. See photos, conditions and how to get there.',
        'Playa Tamarindo en Guánica: una cala apartada y tranquila con aguas claras ideales para snorkel y nadar. Mira fotos, condiciones y cómo llegar.',
    ],
    'isla-de-cabras' => [
        'Isla de Cabras, Toa Baja — Historic Islet & Bay Views',
        'Isla de Cabras, Toa Baja — Islote Histórico y Vistas',
        'Isla de Cabras in Toa Baja — a scenic islet park with San Juan Bay views, picnic spots, restrooms and parking. See photos and how to get there.',
        'Isla de Cabras en Toa Baja: un islote escénico con vistas a la bahía de San Juan, áreas de picnic, baños y estacionamiento. Mira fotos y cómo llegar.',
    ],
    'playa-azul-luquillo' => [
        'Playa Azul, Luquillo — Calm Swim & Easy Parking',
        'Playa Azul, Luquillo — Aguas para Nadar y Acceso Fácil',
        'Playa Azul in Luquillo — a calm, easy-access beach for swimming, with parking right by the sand. See live conditions, photos and how to get there.',
        'Playa Azul en Luquillo: una playa tranquila y de fácil acceso para nadar, con estacionamiento junto a la arena. Mira condiciones, fotos y cómo llegar.',
    ],
    'las-palmas-beach' => [
        'Las Palmas Beach, Manatí — Surf & Scenic Shore',
        'Playa Las Palmas, Manatí — Surf y Costa Escénica',
        'Las Palmas Beach in Manatí — a popular, scenic spot for surfing, with shade and parking. See live conditions, photos and how to get there.',
        'Playa Las Palmas en Manatí: un lugar popular y escénico para surfear, con sombra y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'zoni-beach' => [
        'Zoni Beach, Culebra — Calm, Secluded Snorkel Sand',
        'Playa Zoni, Culebra — Snorkel en Arena Apartada',
        'Zoni Beach on Culebra — a long, secluded stretch of calm, clear water for snorkeling and swimming, with parking. See conditions, photos and directions.',
        'Playa Zoni en Culebra: un tramo largo y apartado de aguas tranquilas y claras para snorkel y nadar, con estacionamiento. Mira fotos y cómo llegar.',
    ],
    'playa-la-mela-cabo-rojo' => [
        'Playa La Mela, Cabo Rojo — Calm, Secluded Swim',
        'Playa La Mela, Cabo Rojo — Nadar en Cala Apartada',
        'Playa La Mela in Cabo Rojo — a calm, secluded beach for swimming, with shade and parking. See live conditions, photos and how to get there.',
        'Playa La Mela en Cabo Rojo: una playa tranquila y apartada para nadar, con sombra y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'charco-el-hippie' => [
        'Charco El Hippie, Naguabo — Secluded Swim Hole',
        'Charco El Hippie, Naguabo — Charco Apartado para Nadar',
        'Charco El Hippie in Naguabo — a secluded natural swim hole with calm, scenic water and parking nearby. See photos, map and how to get there.',
        'Charco El Hippie en Naguabo: un charco natural apartado con aguas tranquilas y escénicas, y estacionamiento cercano. Mira fotos y cómo llegar.',
    ],
    'playa-del-tamarindo' => [
        'Playa del Tamarindo, Aguadilla — Calm Family Swim',
        'Playa del Tamarindo, Aguadilla — Nadar en Familia',
        'Playa del Tamarindo in Aguadilla — calm, shallow water great for families, with rentals, food, shade and parking. See conditions, photos and directions.',
        'Playa del Tamarindo en Aguadilla: aguas tranquilas y poco profundas ideales para familias, con alquiler, kioscos, sombra y estacionamiento. Mira fotos.',
    ],
    'playa-icacos' => [
        'Playa Icacos, Yabucoa — Secluded Shore & Fishing',
        'Playa Icacos, Yabucoa — Costa Apartada y Pesca',
        'Playa Icacos in Yabucoa — a secluded, scenic shore popular for fishing, away from the crowds. See photos, map and how to get there.',
        'Playa Icacos en Yabucoa: una costa apartada y escénica, popular para la pesca y lejos de las multitudes. Mira fotos, mapa y cómo llegar.',
    ],
    'la-posita-pinones' => [
        'La Posita, Piñones — Calm Kid-Friendly Lagoon',
        'La Posita, Piñones — Laguna Tranquila para Niños',
        'La Posita in Piñones, Loíza — a shallow, reef-protected pool with calm water perfect for kids, with parking. See conditions, photos and directions.',
        'La Posita en Piñones, Loíza: una piscina poco profunda protegida por arrecife, con aguas tranquilas ideales para niños y estacionamiento. Mira fotos.',
    ],
    'playa-colora-fajardo' => [
        'Playa Colorá, Fajardo — Secluded Scenic Cove',
        'Playa Colorá, Fajardo — Cala Apartada y Escénica',
        'Playa Colorá in Fajardo — a secluded, scenic cove away from the crowds, reached by a short walk. See photos, map and how to get there.',
        'Playa Colorá en Fajardo: una cala apartada y escénica, lejos de las multitudes, a la que se llega caminando. Mira fotos, mapa y cómo llegar.',
    ],
    'paseo-lineal-isabela' => [
        'Paseo Lineal, Isabela — Beachfront Boardwalk & Swim',
        'Paseo Lineal, Isabela — Malecón Frente al Mar',
        'Paseo Lineal in Isabela — a popular beachfront boardwalk with swimming, food, rentals and parking. See live conditions, photos and how to get there.',
        'Paseo Lineal en Isabela: un malecón popular frente al mar con zona para nadar, kioscos, alquiler y estacionamiento. Mira condiciones, fotos y cómo llegar.',
    ],
    'balneario-municipal-de-aguda' => [
        'Balneario de Aguada — Public Beach, Surf & Lifeguards',
        'Balneario de Aguada — Playa Pública, Surf y Salvavidas',
        'Aguada\'s municipal balneario — a popular public beach for swimming and surfing, with lifeguards, food, picnic areas, restrooms and parking. See directions.',
        'El balneario municipal de Aguada: una playa pública popular para nadar y surfear, con salvavidas, kioscos, áreas de picnic, baños y estacionamiento.',
    ],
    'sandy-beach' => [
        'Sandy Beach, Rincón — Surf Break & Swimming',
        'Sandy Beach, Rincón — Surf y Zona para Nadar',
        'Sandy Beach in Rincón — a lively surf break with swimming between sets, scenic views and parking. See live conditions, photos and how to get there.',
        'Sandy Beach en Rincón: un rompiente popular para surfear con zona para nadar, vistas escénicas y estacionamiento. Mira condiciones, fotos y cómo llegar.',
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
