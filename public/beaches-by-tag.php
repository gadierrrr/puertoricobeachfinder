<?php
/**
 * Dynamic Tag/Amenity Landing Pages
 * Serves SEO-optimized pages for beach tags and amenities
 * URL: /beaches/{tag-slug} or /es/playas/{tag-slug-es}
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$lang = getCurrentLanguage();

// Get tag from URL
$tagSlug = trim($_GET['tag'] ?? '');
if (!$tagSlug) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

// Tag page definitions: slug => config
$tagPages = [
    // Activity tags
    'swimming' => [
        'tag' => 'swimming',
        'type' => 'tag',
        'en' => [
            'title' => 'Best Swimming Beaches in Puerto Rico | 226+ Beaches',
            'description' => 'Find the best swimming beaches in Puerto Rico. Browse 226+ beaches with calm waters, sandy bottoms, and safe swimming conditions across the island.',
            'h1' => 'Best Swimming Beaches in Puerto Rico',
            'intro' => 'Puerto Rico offers over 200 beaches perfect for swimming, from the calm Caribbean waters of the south coast to the protected bays of Vieques and Culebra. Whether you prefer shallow sandy bottoms for wading or deeper waters for open-water swimming, this guide covers every swimmable beach on the island.',
            'slug_es' => 'natacion',
        ],
        'es' => [
            'title' => 'Mejores Playas para Nadar en Puerto Rico | 226+ Playas',
            'description' => 'Encuentra las mejores playas para nadar en Puerto Rico. Explora más de 226 playas con aguas tranquilas y condiciones seguras para nadar.',
            'h1' => 'Mejores Playas para Nadar en Puerto Rico',
            'intro' => 'Puerto Rico ofrece más de 200 playas perfectas para nadar, desde las tranquilas aguas del Caribe en la costa sur hasta las bahías protegidas de Vieques y Culebra.',
        ],
        'faqs' => [
            ['q' => 'Which Puerto Rico beaches have the calmest water for swimming?', 'a' => 'The south and west coast beaches generally have calmer waters. Playa Buyé in Cabo Rojo, Balneario de Boquerón, and Sun Bay in Vieques are consistently calm. The Caribbean side is typically calmer than the Atlantic north coast.'],
            ['q' => 'Are Puerto Rico beaches safe for swimming?', 'a' => 'Most beaches are safe for swimming, but always check local conditions. Balnearios (public beaches) often have lifeguards on weekends. Avoid swimming alone at remote beaches, watch for rip currents on the north coast, and check surf conditions before entering.'],
            ['q' => 'What is the best time of year for swimming in Puerto Rico?', 'a' => 'Puerto Rico is swimmable year-round thanks to water temperatures of 78-84°F. The calmest conditions are typically April through September. Winter months (December-March) bring larger swells to the north coast, making south coast beaches better for swimming.'],
        ],
    ],
    'scenic' => [
        'tag' => 'scenic',
        'type' => 'tag',
        'en' => [
            'title' => 'Most Scenic Beaches in Puerto Rico | 380+ Beautiful Beaches',
            'description' => 'Discover the most scenic and beautiful beaches in Puerto Rico. Browse 380+ stunning beaches with dramatic cliffs, turquoise waters, and pristine sand.',
            'h1' => 'Most Scenic Beaches in Puerto Rico',
            'intro' => 'From dramatic limestone cliffs in Aguadilla to the bioluminescent bays of Vieques, Puerto Rico\'s coastline offers some of the most breathtaking beach scenery in the Caribbean. Explore over 380 scenic beaches perfect for photography, sunsets, and unforgettable views.',
            'slug_es' => 'escenicas',
        ],
        'es' => [
            'title' => 'Playas Más Escénicas de Puerto Rico | 380+ Playas Hermosas',
            'description' => 'Descubre las playas más escénicas y hermosas de Puerto Rico. Explora más de 380 playas impresionantes.',
            'h1' => 'Playas Más Escénicas de Puerto Rico',
            'intro' => 'Desde los dramáticos acantilados de piedra caliza en Aguadilla hasta las bahías bioluminiscentes de Vieques, la costa de Puerto Rico ofrece algunos de los paisajes playeros más impresionantes del Caribe.',
        ],
        'faqs' => [
            ['q' => 'What are the most photogenic beaches in Puerto Rico?', 'a' => 'Flamenco Beach in Culebra is consistently rated among the world\'s most beautiful. Crash Boat Beach in Aguadilla features a colorful pier. Playa Sucia in Cabo Rojo has dramatic cliffs and a lighthouse. La Playuela offers stunning sunset views.'],
            ['q' => 'Where can I see the best sunsets on Puerto Rico beaches?', 'a' => 'The west coast offers the best sunset views. Top spots include Playa Buyé and Playa Sucia in Cabo Rojo, Rincón beaches like Domes and Sandy Beach, and Crash Boat Beach in Aguadilla.'],
            ['q' => 'Are scenic beaches in Puerto Rico accessible?', 'a' => 'Many scenic beaches require hiking or rough roads. However, beaches like Flamenco, Crash Boat, and Balneario de Boquerón are both scenic and easily accessible with parking and facilities.'],
        ],
    ],
    'calm-waters' => [
        'tag' => 'calm-waters',
        'type' => 'tag',
        'en' => [
            'title' => 'Calm Water Beaches in Puerto Rico | 105+ Peaceful Beaches',
            'description' => 'Find calm water beaches in Puerto Rico perfect for families and relaxation. Browse 105+ beaches with gentle waves, protected bays, and tranquil swimming conditions.',
            'h1' => 'Calm Water Beaches in Puerto Rico',
            'intro' => 'Looking for beaches without big waves? Puerto Rico has over 100 calm water beaches ideal for families with young children, snorkeling, and relaxed swimming. The south coast Caribbean beaches and protected bays offer the gentlest conditions year-round.',
            'slug_es' => 'aguas-tranquilas',
        ],
        'es' => [
            'title' => 'Playas de Aguas Tranquilas en Puerto Rico | 105+ Playas',
            'description' => 'Encuentra playas de aguas tranquilas en Puerto Rico perfectas para familias. Más de 105 playas con olas suaves.',
            'h1' => 'Playas de Aguas Tranquilas en Puerto Rico',
            'intro' => '¿Buscas playas sin olas grandes? Puerto Rico tiene más de 100 playas de aguas tranquilas ideales para familias con niños pequeños, snorkel y natación relajada.',
        ],
        'faqs' => [
            ['q' => 'Which side of Puerto Rico has the calmest beaches?', 'a' => 'The south and southwest coasts, facing the Caribbean Sea, generally have the calmest waters. Guánica, Cabo Rojo, and Lajas offer consistently calm conditions. The islands of Vieques and Culebra also have protected bays with calm water.'],
            ['q' => 'Are calm water beaches good for snorkeling?', 'a' => 'Yes! Calm water beaches are often the best for snorkeling because visibility is better and you can swim comfortably. Top calm-water snorkeling spots include Carlos Rosario Beach in Culebra, Steps Beach in Rincón, and Playa Tamarindo in Culebra.'],
            ['q' => 'Do calm water beaches have sand or rocks?', 'a' => 'Most calm water beaches in Puerto Rico have sandy bottoms, especially on the south coast. Some protected coves may have rocky areas near the shore, so water shoes are recommended at beaches like those in Guánica and some Vieques beaches.'],
        ],
    ],
    'fishing' => [
        'tag' => 'fishing',
        'type' => 'tag',
        'en' => [
            'title' => 'Best Fishing Beaches in Puerto Rico | 67+ Spots',
            'description' => 'Discover the best fishing beaches in Puerto Rico. Find 67+ shore fishing spots, piers, and coastal areas popular with local anglers across the island.',
            'h1' => 'Best Fishing Beaches in Puerto Rico',
            'intro' => 'Puerto Rico\'s diverse coastline offers excellent shore fishing opportunities. From rocky points where tarpon run to sandy beaches where pompano feed, discover over 67 beaches where locals and visitors cast their lines.',
            'slug_es' => 'pesca',
        ],
        'es' => [
            'title' => 'Mejores Playas de Pesca en Puerto Rico | 67+ Lugares',
            'description' => 'Descubre las mejores playas de pesca en Puerto Rico. Encuentra más de 67 lugares costeros populares entre pescadores.',
            'h1' => 'Mejores Playas de Pesca en Puerto Rico',
            'intro' => 'La diversa costa de Puerto Rico ofrece excelentes oportunidades para la pesca desde la orilla.',
        ],
        'faqs' => [
            ['q' => 'Do I need a fishing license to fish from beaches in Puerto Rico?', 'a' => 'Recreational shore fishing in Puerto Rico generally does not require a license for personal consumption. However, spearfishing and boat fishing require permits from the DNER (Department of Natural and Environmental Resources). Always check current regulations.'],
            ['q' => 'What fish can I catch from Puerto Rico beaches?', 'a' => 'Common catches include snook, tarpon, jacks, pompano, snapper, barracuda, and bonefish. The north coast rocky shores are good for reef fish, while south coast flats are excellent for bonefish and permit.'],
            ['q' => 'What is the best time for beach fishing in Puerto Rico?', 'a' => 'Early morning and late afternoon are best. The winter months (November-February) bring cooler water and active fish. Full moon and new moon periods create stronger tidal movements that improve fishing at river mouths and points.'],
        ],
    ],
    'accessible' => [
        'tag' => 'accessible',
        'type' => 'tag',
        'en' => [
            'title' => 'Accessible Beaches in Puerto Rico | Wheelchair & Mobility Friendly',
            'description' => 'Find wheelchair accessible and mobility-friendly beaches in Puerto Rico. Discover beaches with ramps, accessible paths, beach wheelchairs, and ADA facilities.',
            'h1' => 'Accessible Beaches in Puerto Rico',
            'intro' => 'Puerto Rico is working to make its beautiful coastline accessible to everyone. Several beaches now offer wheelchair ramps, accessible restrooms, beach mats, and specialized beach wheelchairs. Here are the beaches with the best accessibility features.',
            'slug_es' => 'accesibles',
        ],
        'es' => [
            'title' => 'Playas Accesibles en Puerto Rico | Sillas de Ruedas y Movilidad',
            'description' => 'Encuentra playas accesibles para sillas de ruedas en Puerto Rico. Descubre playas con rampas, caminos accesibles e instalaciones ADA.',
            'h1' => 'Playas Accesibles en Puerto Rico',
            'intro' => 'Puerto Rico está trabajando para hacer su hermosa costa accesible para todos. Varias playas ahora ofrecen rampas para sillas de ruedas, baños accesibles y sillas de playa especializadas.',
        ],
        'faqs' => [
            ['q' => 'Which Puerto Rico beaches have wheelchair access?', 'a' => 'Several balnearios (public beaches) have wheelchair access, including Balneario de Carolina, Balneario de Luquillo, Balneario de Boquerón, and Balneario El Escambrón in San Juan. These typically have paved paths, accessible restrooms, and sometimes beach wheelchairs.'],
            ['q' => 'Can I rent beach wheelchairs in Puerto Rico?', 'a' => 'Beach wheelchair availability varies. Balneario de Luquillo\'s Mar Sin Barreras program offers free beach wheelchairs. Contact individual beach offices or the municipal tourism office in advance to confirm availability and reserve equipment.'],
            ['q' => 'Are there accessible beach resorts in Puerto Rico?', 'a' => 'Yes, major resort areas in San Juan, Dorado, and Fajardo have ADA-compliant beach access. The Condado and Isla Verde areas in San Juan offer the most accessible beachfront infrastructure.'],
        ],
    ],
    'diving' => [
        'tag' => 'diving',
        'type' => 'tag',
        'en' => [
            'title' => 'Best Diving Beaches in Puerto Rico | 18+ Dive Sites',
            'description' => 'Discover the best diving beaches in Puerto Rico. Find shore-entry dive sites, walls, reefs, and underwater caves across the island.',
            'h1' => 'Best Diving Beaches in Puerto Rico',
            'intro' => 'Puerto Rico\'s underwater world rivals its stunning beaches. From the famous Wall at Desecheo Island to shore-entry dives along the west coast, discover dive sites accessible right from the beach. Crystal-clear Caribbean waters offer visibility up to 100 feet.',
            'slug_es' => 'buceo',
        ],
        'es' => [
            'title' => 'Mejores Playas de Buceo en Puerto Rico | 18+ Sitios',
            'description' => 'Descubre las mejores playas de buceo en Puerto Rico. Encuentra sitios de buceo con acceso desde la playa.',
            'h1' => 'Mejores Playas de Buceo en Puerto Rico',
            'intro' => 'El mundo submarino de Puerto Rico rivaliza con sus impresionantes playas. Descubre sitios de buceo accesibles directamente desde la playa.',
        ],
        'faqs' => [
            ['q' => 'Where are the best shore dives in Puerto Rico?', 'a' => 'Steps Beach and Tres Palmas in Rincón offer excellent shore-entry dives with sea turtles and coral gardens. Crash Boat in Aguadilla, Escambrón in San Juan, and the beaches of La Parguera in Lajas are also popular shore dive spots.'],
            ['q' => 'What can I see diving in Puerto Rico?', 'a' => 'Puerto Rico\'s waters are home to sea turtles, rays, nurse sharks, tropical fish, coral reefs, sea walls, and underwater caves. The bioluminescent organisms in some bays create unique night diving experiences.'],
            ['q' => 'Do I need certification to dive at Puerto Rico beaches?', 'a' => 'Yes, most dive shops require at least Open Water certification. Many shops offer Discover Scuba Diving experiences for beginners. Snorkeling requires no certification and many dive sites are also excellent snorkel spots.'],
        ],
    ],
    'camping' => [
        'tag' => 'camping',
        'type' => 'tag',
        'en' => [
            'title' => 'Camping Beaches in Puerto Rico | Beach Camping Guide',
            'description' => 'Find the best camping beaches in Puerto Rico. Discover beachfront campsites, primitive camping spots, and nature reserves where you can camp by the ocean.',
            'h1' => 'Camping Beaches in Puerto Rico',
            'intro' => 'Wake up to the sound of waves at Puerto Rico\'s beach camping spots. From developed campgrounds at natural reserves to primitive beachfront camping on remote islands, here are the best places to pitch a tent by the ocean.',
            'slug_es' => 'acampar',
        ],
        'es' => [
            'title' => 'Playas para Acampar en Puerto Rico | Guía de Camping',
            'description' => 'Encuentra las mejores playas para acampar en Puerto Rico. Descubre campamentos frente al mar y reservas naturales.',
            'h1' => 'Playas para Acampar en Puerto Rico',
            'intro' => 'Despierta con el sonido de las olas en los lugares de camping playero de Puerto Rico.',
        ],
        'faqs' => [
            ['q' => 'Can you camp on beaches in Puerto Rico?', 'a' => 'Yes! Several beaches allow camping, including Flamenco Beach in Culebra (the most popular), Sun Bay in Vieques, and several DRNA-managed natural reserves. Permits are usually required and can be obtained from the DRNA or municipal offices.'],
            ['q' => 'How much does beach camping cost in Puerto Rico?', 'a' => 'Camping fees vary from free to about $20 per night. Flamenco Beach camping costs around $20/night. DRNA natural reserve camping is typically $10-15/night. Some primitive spots are free but require a permit.'],
            ['q' => 'What should I bring for beach camping in Puerto Rico?', 'a' => 'Essential items include a waterproof tent, insect repellent (mosquitoes are common), reef-safe sunscreen, water containers (potable water may not be available), a camping stove, and a headlamp. Pack out all trash.'],
        ],
    ],
    'popular' => [
        'tag' => 'popular',
        'type' => 'tag',
        'en' => [
            'title' => 'Most Popular Beaches in Puerto Rico | Top-Rated by Visitors',
            'description' => 'Explore the most popular beaches in Puerto Rico, rated highest by visitors. Find the 81+ most-visited beaches with the best reviews and ratings.',
            'h1' => 'Most Popular Beaches in Puerto Rico',
            'intro' => 'These are the beaches that visitors love the most. Based on visitor ratings and review counts, these 81+ beaches consistently earn the highest marks for their beauty, facilities, and overall experience.',
            'slug_es' => 'populares',
        ],
        'es' => [
            'title' => 'Playas Más Populares de Puerto Rico | Mejor Calificadas',
            'description' => 'Explora las playas más populares de Puerto Rico, las mejor calificadas por visitantes.',
            'h1' => 'Playas Más Populares de Puerto Rico',
            'intro' => 'Estas son las playas que más gustan a los visitantes. Basado en calificaciones y reseñas, estas 81+ playas consistentemente reciben las mejores marcas.',
        ],
        'faqs' => [
            ['q' => 'What is the #1 most popular beach in Puerto Rico?', 'a' => 'Flamenco Beach in Culebra is consistently rated the most popular, often appearing on world\'s best beaches lists. On the main island, Crash Boat Beach in Aguadilla and Condado Beach in San Juan are the most visited.'],
            ['q' => 'Are popular beaches in Puerto Rico crowded?', 'a' => 'The most popular beaches can be crowded on weekends and holidays, especially during summer (June-August) and around Christmas. Visit on weekday mornings for a quieter experience, or explore less-known alternatives nearby.'],
            ['q' => 'Do popular beaches have facilities?', 'a' => 'Most popular beaches have good facilities including parking, restrooms, food vendors or nearby restaurants, and shade structures. Government-managed balnearios typically have the best infrastructure.'],
        ],
    ],
    'surfing' => [
        'tag' => 'surfing',
        'type' => 'tag',
        'en' => [
            'title' => 'Best Surfing Beaches in Puerto Rico | 57+ Surf Spots',
            'description' => 'Find the best surfing beaches in Puerto Rico. Browse 57+ world-class surf spots from Rincón to Aguadilla, with breaks for every skill level.',
            'h1' => 'Best Surfing Beaches in Puerto Rico',
            'intro' => 'Puerto Rico is the Caribbean\'s surfing capital, hosting world-class breaks along the west and north coasts. From the legendary winter swells at Rincón, Aguadilla, and Isabela to gentler beach breaks perfect for learning, these 57+ beaches cover every skill level and every season.',
            'slug_es' => 'surf',
        ],
        'es' => [
            'title' => 'Mejores Playas para Surfear en Puerto Rico | 57+ Spots',
            'description' => 'Encuentra las mejores playas para surfear en Puerto Rico. Más de 57 spots de clase mundial desde Rincón hasta Aguadilla, para todos los niveles.',
            'h1' => 'Mejores Playas para Surfear en Puerto Rico',
            'intro' => 'Puerto Rico es la capital caribeña del surf, con olas de clase mundial en las costas oeste y norte. Desde las legendarias marejadas invernales de Rincón, Aguadilla e Isabela hasta olas suaves ideales para aprender, estas 57+ playas ofrecen algo para cada nivel.',
        ],
        'faqs' => [
            ['q' => 'Where are the best surfing beaches in Puerto Rico?', 'a' => 'The west coast is Puerto Rico\'s surfing heartland. Rincón (Domes, Maria\'s, Tres Palmas), Aguadilla (Crash Boat, Wilderness, Gas Chambers), and Isabela (Jobos, Shacks) host the most consistent and famous breaks. The north coast also fires during winter swells, particularly around Arecibo and Manatí.'],
            ['q' => 'When is the best time of year for surfing in Puerto Rico?', 'a' => 'The prime surf season runs from October through March, when North Atlantic swells reach the west and north coasts. Winter brings head-high to overhead waves at most spots, with the biggest swells in December and January. Summer surf is smaller but still surfable, especially early mornings before the trade winds pick up.'],
            ['q' => 'Are there surf spots for beginners in Puerto Rico?', 'a' => 'Yes. Sandy Beach and Playa Shacks in Isabela, and Jobos on smaller days, are popular beginner spots with sandy bottoms and forgiving waves. Rincón\'s Sandy Beach and several Aguadilla breaks also suit learners. Many local surf schools offer lessons and board rentals at these beaches.'],
        ],
    ],
    'snorkeling' => [
        'tag' => 'snorkeling',
        'type' => 'tag',
        'en' => [
            'title' => 'Best Snorkeling Beaches in Puerto Rico | 95+ Snorkel Spots',
            'description' => 'Discover the best snorkeling beaches in Puerto Rico. Browse 95+ spots with coral reefs, tropical fish, and crystal-clear Caribbean water.',
            'h1' => 'Best Snorkeling Beaches in Puerto Rico',
            'intro' => 'Puerto Rico\'s reefs, protected bays, and offshore cays offer some of the Caribbean\'s best snorkeling. Culebra and Vieques are world-famous for clear water and healthy coral, while spots around Guánica, Fajardo, and La Parguera let you drift over reef gardens teeming with tropical fish. Explore these 95+ snorkel-friendly beaches below.',
            'slug_es' => 'snorkel',
        ],
        'es' => [
            'title' => 'Mejores Playas para Hacer Snorkel en Puerto Rico | 95+ Spots',
            'description' => 'Descubre las mejores playas para hacer snorkel en Puerto Rico. Más de 95 lugares con arrecifes de coral, peces tropicales y aguas cristalinas.',
            'h1' => 'Mejores Playas para Hacer Snorkel en Puerto Rico',
            'intro' => 'Los arrecifes de Puerto Rico, sus bahías protegidas y cayos cercanos ofrecen algunos de los mejores lugares para hacer snorkel en el Caribe. Culebra y Vieques son famosas por sus aguas cristalinas y corales saludables, mientras que Guánica, Fajardo y La Parguera permiten flotar sobre jardines de arrecife llenos de vida.',
        ],
        'faqs' => [
            ['q' => 'What are the top snorkeling beaches in Puerto Rico?', 'a' => 'Carlos Rosario and Tamarindo in Culebra are widely considered the island\'s best, with easy shore entry and healthy reef just offshore. Other top picks include Playa Escondida and Sun Bay on Vieques, Playa Tres in Guánica, and Seven Seas and La Cordillera cays near Fajardo. Steps Beach in Rincón is excellent during calmer months.'],
            ['q' => 'Do I need to bring my own snorkel gear?', 'a' => 'It\'s a good idea. While outfitters in Culebra, Fajardo, Rincón, and La Parguera rent mask, snorkel, and fins, more remote beaches have no rentals available. Packing your own gear guarantees a good fit and lets you snorkel spontaneously wherever the water looks clear.'],
            ['q' => 'Is snorkeling in Puerto Rico safe for beginners?', 'a' => 'Yes — many top spots have calm, shallow, protected water ideal for first-timers, including Tamarindo (Culebra), Seven Seas (Fajardo), and Steps Beach (Rincón) on mild days. Always snorkel with a buddy, check local conditions, avoid touching or stepping on coral, and watch for boat traffic in busier areas.'],
        ],
    ],
    'family-friendly' => [
        'tag' => 'family-friendly',
        'type' => 'tag',
        'en' => [
            'title' => 'Family-Friendly Beaches in Puerto Rico | 155+ Beaches for Kids',
            'description' => 'Plan a beach day with kids. Browse 155+ family-friendly beaches in Puerto Rico with calm water, shallow entry, parking, restrooms, and lifeguards.',
            'h1' => 'Family-Friendly Beaches in Puerto Rico',
            'intro' => 'Traveling with kids? Puerto Rico has 155+ beaches built for families, with calm shallow water, shaded picnic areas, food kioskos, and on-duty lifeguards at the island\'s balnearios. These pages help you find the right mix of gentle surf, parking, and facilities for your family\'s next beach day.',
            'slug_es' => 'familiares',
        ],
        'es' => [
            'title' => 'Playas Familiares en Puerto Rico | 155+ Playas para Niños',
            'description' => 'Planifica un día de playa con niños. Más de 155 playas familiares en Puerto Rico con aguas tranquilas, entrada suave, estacionamiento y salvavidas.',
            'h1' => 'Playas Familiares en Puerto Rico',
            'intro' => '¿Viajas con niños? Puerto Rico tiene más de 155 playas pensadas para familias, con aguas tranquilas y poco profundas, áreas de picnic con sombra, kioscos de comida y salvavidas en los balnearios. Estas páginas te ayudan a encontrar la mezcla ideal de olas suaves, estacionamiento y facilidades.',
        ],
        'faqs' => [
            ['q' => 'Which Puerto Rico beaches are best for small children?', 'a' => 'Balneario de Luquillo, Balneario de Boquerón, Playa Buyé in Cabo Rojo, Sun Bay in Vieques, and Seven Seas in Fajardo all have calm, shallow water and good facilities. The south and west coasts are generally calmer than the Atlantic north coast, making them safer choices for toddlers.'],
            ['q' => 'Do family beaches have lifeguards and restrooms?', 'a' => 'The island\'s balnearios (public beaches) staff lifeguards on weekends and many weekdays, and provide restrooms, showers, picnic shelters, and parking. Luquillo, Carolina, Boquerón, Escambrón, and Seven Seas are reliable choices. Smaller local beaches may not have lifeguards, so always check before you go.'],
            ['q' => 'Which balnearios are best for families?', 'a' => 'Balneario de Luquillo is the most popular family beach, with rows of food kioskos, calm water, and accessibility features. Balneario de Carolina near San Juan and Balneario de Boquerón on the west coast are also excellent. Balneario Sun Bay in Vieques offers a quieter, more scenic family beach experience.'],
        ],
    ],
    'secluded' => [
        'tag' => 'secluded',
        'type' => 'tag',
        'en' => [
            'title' => 'Secluded Beaches in Puerto Rico | 121+ Hidden & Quiet Beaches',
            'description' => 'Escape the crowds. Find 121+ secluded, hidden, and off-the-beaten-path beaches in Puerto Rico, from Vieques coves to remote west coast stretches.',
            'h1' => 'Secluded Beaches in Puerto Rico',
            'intro' => 'If you\'d rather share a beach with the pelicans than the crowds, Puerto Rico has 121+ secluded options. Many require a short hike, a 4WD ride, or a boat to reach — and most have no facilities, so you\'ll want to pack water, food, shade, and a trash bag. What you get in return is empty sand, clear water, and the rare Caribbean luxury of silence.',
            'slug_es' => 'aisladas',
        ],
        'es' => [
            'title' => 'Playas Aisladas en Puerto Rico | 121+ Playas Escondidas',
            'description' => 'Escapa de las multitudes. Encuentra más de 121 playas aisladas y escondidas en Puerto Rico, desde las calas de Vieques hasta la costa oeste remota.',
            'h1' => 'Playas Aisladas en Puerto Rico',
            'intro' => 'Si prefieres compartir la playa con los pelícanos en vez de con la multitud, Puerto Rico tiene más de 121 opciones aisladas. Muchas requieren una caminata corta, 4x4 o bote, y la mayoría no tienen facilidades, así que trae agua, comida, sombra y una bolsa para basura.',
        ],
        'faqs' => [
            ['q' => 'What are the most secluded beaches in Puerto Rico?', 'a' => 'Playa La Chiva and Playa Caracas on Vieques, Playa Sucia\'s surrounding coves in Cabo Rojo, Playuela and the western cliffs of Aguadilla, and the beaches of Mona Island all reward the effort of getting there with near-total solitude. The offshore cays of La Cordillera near Fajardo are also essentially private on weekdays.'],
            ['q' => 'How do I get to secluded beaches in Puerto Rico?', 'a' => 'Some are a short walk from a parking area, others need a 4WD vehicle or a boat. Playa La Chiva and Caracas on Vieques are reached via the former Navy base roads — 4WD recommended but not required. Mona Island requires a licensed boat charter and advance permits. Always check current road and access conditions before setting out.'],
            ['q' => 'What should I bring to a secluded beach?', 'a' => 'Pack more than you think you\'ll need: plenty of water, snacks or lunch, sunscreen, shade (umbrella or beach tent), reef-safe bug spray, a trash bag, a charged phone, and a first-aid kit. Most secluded beaches have no vendors, restrooms, or cell service. Let someone know where you\'re going and plan to leave before dusk.'],
        ],
    ],
    // Amenity-based pages
    'with-parking' => [
        'tag' => 'parking',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Parking in Puerto Rico | 357+ Beaches',
            'description' => 'Find beaches with parking in Puerto Rico. Browse 357+ beaches with free street parking, paid lots, or designated parking areas near the shore.',
            'h1' => 'Beaches with Parking in Puerto Rico',
            'intro' => 'Parking can make or break a beach trip in Puerto Rico. Over 350 beaches across the island have some form of parking, from free roadside spots to organized lots. Use this guide to find beaches where you can park without hassle.',
            'slug_es' => 'con-estacionamiento',
        ],
        'es' => [
            'title' => 'Playas con Estacionamiento en Puerto Rico | 357+ Playas',
            'description' => 'Encuentra playas con estacionamiento en Puerto Rico. Más de 357 playas con estacionamiento gratuito o pagado.',
            'h1' => 'Playas con Estacionamiento en Puerto Rico',
            'intro' => 'El estacionamiento puede definir un viaje a la playa en Puerto Rico. Más de 350 playas tienen algún tipo de estacionamiento.',
        ],
        'faqs' => [
            ['q' => 'Is beach parking free in Puerto Rico?', 'a' => 'Most beach parking is free, especially at roadside spots and smaller beaches. Government balnearios charge $5-7 per vehicle. Popular tourist beaches near resorts may have paid lots ($5-20). Street parking near urban beaches is usually free but limited.'],
            ['q' => 'Which popular beaches have the best parking?', 'a' => 'Balneario de Luquillo has a large parking area. Crash Boat Beach has free parking along the road. Boquerón has ample parking. Flamenco Beach in Culebra has a parking area near the campgrounds.'],
            ['q' => 'What should I know about beach parking in Puerto Rico?', 'a' => 'Never leave valuables visible in your car. Arrive early on weekends for popular beaches. Avoid blocking residential driveways. Some remote beaches only have informal roadside parking. A rental car is essential for exploring beyond San Juan beaches.'],
        ],
    ],
    'with-restrooms' => [
        'tag' => 'restrooms',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Restrooms in Puerto Rico | 62+ Beaches',
            'description' => 'Find beaches with restroom facilities in Puerto Rico. Browse 62+ beaches with public bathrooms, changing rooms, and sanitary facilities.',
            'h1' => 'Beaches with Restrooms in Puerto Rico',
            'intro' => 'Not all Puerto Rico beaches have facilities. If restrooms are a must for your beach day, these 62+ beaches have public bathrooms available. Most government-operated balnearios offer the most reliable restroom facilities.',
            'slug_es' => 'con-banos',
        ],
        'es' => [
            'title' => 'Playas con Baños en Puerto Rico | 62+ Playas',
            'description' => 'Encuentra playas con baños públicos en Puerto Rico. Más de 62 playas con instalaciones sanitarias.',
            'h1' => 'Playas con Baños en Puerto Rico',
            'intro' => 'No todas las playas de Puerto Rico tienen instalaciones. Si los baños son imprescindibles, estas 62+ playas cuentan con baños públicos.',
        ],
        'faqs' => [
            ['q' => 'Do Puerto Rico beaches have restrooms?', 'a' => 'Government-operated balnearios almost always have restroom facilities. Wild and remote beaches typically do not. About 62 beaches across the island have public restrooms, concentrated at balnearios and popular tourist beaches.'],
            ['q' => 'Are beach restrooms in Puerto Rico clean?', 'a' => 'Facility quality varies. Balnearios are generally well-maintained, especially on weekends when attendants are present. Popular tourist beaches usually have better-maintained facilities. Always bring your own toilet paper and hand sanitizer as a precaution.'],
        ],
    ],
    'with-showers' => [
        'tag' => 'showers',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Showers in Puerto Rico | 32+ Beaches',
            'description' => 'Find beaches with shower facilities in Puerto Rico. Browse 32+ beaches with rinse-off showers and changing areas.',
            'h1' => 'Beaches with Showers in Puerto Rico',
            'intro' => 'Want to rinse off the salt and sand before heading home? These 32+ beaches in Puerto Rico have shower facilities, making them ideal for a comfortable beach day without bringing all the sand back to your car.',
            'slug_es' => 'con-duchas',
        ],
        'es' => [
            'title' => 'Playas con Duchas en Puerto Rico | 32+ Playas',
            'description' => 'Encuentra playas con duchas en Puerto Rico. Más de 32 playas con instalaciones para enjuagarse.',
            'h1' => 'Playas con Duchas en Puerto Rico',
            'intro' => '¿Quieres enjuagarte la sal y la arena antes de ir a casa? Estas 32+ playas tienen duchas disponibles.',
        ],
        'faqs' => [
            ['q' => 'Which beaches in Puerto Rico have showers?', 'a' => 'Most government balnearios have outdoor shower facilities, including Luquillo, Boquerón, Carolina, and Escambrón. Some resort-adjacent beaches also offer public showers. Look for the shower amenity tag on each beach page.'],
        ],
    ],
    'with-lifeguard' => [
        'tag' => 'lifeguard',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Lifeguards in Puerto Rico | 27+ Beaches',
            'description' => 'Find beaches with lifeguards in Puerto Rico. Browse 27+ beaches with lifeguard services for safer swimming.',
            'h1' => 'Beaches with Lifeguards in Puerto Rico',
            'intro' => 'For the safest swimming experience, choose a beach with lifeguard coverage. These 27+ beaches in Puerto Rico have lifeguards on duty, primarily at government-operated balnearios during peak hours and weekends.',
            'slug_es' => 'con-salvavidas',
        ],
        'es' => [
            'title' => 'Playas con Salvavidas en Puerto Rico | 27+ Playas',
            'description' => 'Encuentra playas con salvavidas en Puerto Rico. Más de 27 playas con servicio de salvavidas.',
            'h1' => 'Playas con Salvavidas en Puerto Rico',
            'intro' => 'Para nadar con mayor seguridad, elige una playa con cobertura de salvavidas. Estas 27+ playas tienen salvavidas de servicio.',
        ],
        'faqs' => [
            ['q' => 'Do Puerto Rico beaches have lifeguards?', 'a' => 'Only some beaches have lifeguards, primarily the government-operated balnearios. Coverage is typically weekends and holidays from 8:30 AM to 5:00 PM. Always swim near the lifeguard station and obey flag warnings.'],
            ['q' => 'What do the beach flag colors mean in Puerto Rico?', 'a' => 'Green means safe for swimming. Yellow means caution/moderate conditions. Red means dangerous conditions, swimming not recommended. Double red means beach is closed to swimming. Always check the flags before entering the water.'],
        ],
    ],
    'with-picnic-areas' => [
        'tag' => 'picnic-areas',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Picnic Areas in Puerto Rico | 42+ Beaches',
            'description' => 'Find beaches with picnic facilities in Puerto Rico. Browse 42+ beaches with tables, gazebos, grills, and shaded picnic areas.',
            'h1' => 'Beaches with Picnic Areas in Puerto Rico',
            'intro' => 'Planning a beach cookout or family gathering? These 42+ beaches offer picnic tables, gazebos, grills, and shaded areas perfect for a full day at the beach with food and fun.',
            'slug_es' => 'con-areas-picnic',
        ],
        'es' => [
            'title' => 'Playas con Áreas de Picnic en Puerto Rico | 42+ Playas',
            'description' => 'Encuentra playas con áreas de picnic en Puerto Rico. Más de 42 playas con mesas, gazebos y parrillas.',
            'h1' => 'Playas con Áreas de Picnic en Puerto Rico',
            'intro' => '¿Planeas una parrillada en la playa? Estas 42+ playas ofrecen mesas, gazebos y áreas sombreadas perfectas para un día completo.',
        ],
        'faqs' => [
            ['q' => 'Can you have BBQs on Puerto Rico beaches?', 'a' => 'Yes, many balnearios have designated grill areas. Bring your own charcoal and supplies. Clean up thoroughly after use. Some beaches prohibit open fires on the sand, so stick to the provided grill stations.'],
            ['q' => 'Do I need to reserve picnic areas?', 'a' => 'Most picnic areas are first-come, first-served. Some popular balnearios like Luquillo offer gazebo reservations for large groups through the DRNA or municipal office. Arrive early on weekends to secure a spot.'],
        ],
    ],
    'with-food' => [
        'tag' => 'food',
        'type' => 'amenity',
        'en' => [
            'title' => 'Beaches with Food & Restaurants in Puerto Rico | 90+ Beaches',
            'description' => 'Find beaches with food vendors, kiosks, and restaurants in Puerto Rico. Browse 90+ beaches where you can grab a bite without leaving the sand.',
            'h1' => 'Beaches with Food & Restaurants in Puerto Rico',
            'intro' => 'No need to pack a cooler! These 90+ beaches have food vendors, kiosks, or restaurants within walking distance. From fresh alcapurrias at Piñones to seafood at Luquillo\'s famous kiosks, eat your way along Puerto Rico\'s coast.',
            'slug_es' => 'con-comida',
        ],
        'es' => [
            'title' => 'Playas con Comida y Restaurantes en Puerto Rico | 90+ Playas',
            'description' => 'Encuentra playas con vendedores de comida, kioskos y restaurantes en Puerto Rico.',
            'h1' => 'Playas con Comida y Restaurantes en Puerto Rico',
            'intro' => '¡No necesitas llevar nevera! Estas 90+ playas tienen vendedores de comida, kioskos o restaurantes a poca distancia.',
        ],
        'faqs' => [
            ['q' => 'Which Puerto Rico beaches have the best food?', 'a' => 'Luquillo Beach is famous for its row of kioskos serving local favorites. Piñones boardwalk offers the best alcapurrias and bacalaítos. Boquerón has excellent seafood. Crash Boat Beach in Aguadilla has popular food trucks on weekends.'],
            ['q' => 'What food should I try at Puerto Rico beaches?', 'a' => 'Must-try beach foods include alcapurrias (fritters), bacalaítos (cod fritters), empanadillas, piña coladas, fresh coconut water, and mofongo. Many kiosks serve fresh-caught seafood. Don\'t miss the traditional limber (frozen fruit treats).'],
        ],
    ],
];

// Resolve Spanish slugs to English keys
if (!isset($tagPages[$tagSlug])) {
    foreach ($tagPages as $enSlug => $cfg) {
        if (($cfg['en']['slug_es'] ?? '') === $tagSlug) {
            $tagSlug = $enSlug;
            $lang = 'es';
            break;
        }
    }
}

// Validate tag slug
if (!isset($tagPages[$tagSlug])) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

$config = $tagPages[$tagSlug];
$tagData = $config[$lang] ?? $config['en'];
$tagFaqs = $config['faqs'] ?? [];

// Fetch beaches by tag or amenity
if ($config['type'] === 'amenity') {
    $beaches = query("
        SELECT b.*
        FROM beaches b
        JOIN beach_amenities ba ON ba.beach_id = b.id
        WHERE ba.amenity = :amenity
        AND b.publish_status = 'published' AND (b.location_type = 'beach' OR b.location_type IS NULL)
        ORDER BY
            CASE WHEN b.google_rating IS NOT NULL AND b.google_review_count >= 10 THEN 1
                 WHEN b.google_rating IS NOT NULL THEN 2
                 ELSE 3 END,
            (COALESCE(b.google_rating, 0) * (1.0 + COALESCE(b.google_review_count, 0) / 100.0)) DESC,
            b.name ASC
    ", [':amenity' => $config['tag']]);
} else {
    $beaches = query("
        SELECT b.*
        FROM beaches b
        JOIN beach_tags bt ON bt.beach_id = b.id
        WHERE bt.tag = :tag
        AND b.publish_status = 'published' AND (b.location_type = 'beach' OR b.location_type IS NULL)
        ORDER BY
            CASE WHEN b.google_rating IS NOT NULL AND b.google_review_count >= 10 THEN 1
                 WHEN b.google_rating IS NOT NULL THEN 2
                 ELSE 3 END,
            (COALESCE(b.google_rating, 0) * (1.0 + COALESCE(b.google_review_count, 0) / 100.0)) DESC,
            b.name ASC
    ", [':tag' => $config['tag']]);
}

if (empty($beaches)) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

attachBeachMetadata($beaches);

$beachCount = count($beaches);
$pageTitle = $tagData['title'];
$pageDescription = $tagData['description'];
$enSlug = $tagSlug;
$esSlug = $config['en']['slug_es'] ?? $tagSlug;
$canonicalPath = '/beaches/' . $enSlug;
$canonicalUrl = absoluteUrl($canonicalPath);

// Structured data
$extraHead = articleSchema(
    $tagData['h1'],
    $pageDescription,
    $canonicalPath,
    $beaches[0]['cover_image'] ?? null,
    date('Y-m-d')
);
$extraHead .= collectionPageSchema($tagData['h1'], $pageDescription, array_slice($beaches, 0, 20));
$extraHead .= websiteSchema();

if (!empty($tagFaqs)) {
    $faqItems = [];
    foreach ($tagFaqs as $faq) {
        $faqItems[] = ['question' => $faq['q'], 'answer' => $faq['a']];
    }
    $extraHead .= faqSchema($faqItems);
}

// Hreflang tags are emitted by header.php (via localeRouteMatch + routeUrl for beaches_by_tag)

// Breadcrumbs
$breadcrumbs = [
    ['name' => $lang === 'es' ? 'Inicio' : 'Home', 'url' => routeUrl('home', $lang)],
    ['name' => $tagData['h1']]
];

// User favorites
$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Calculate stats for hero
$ratedBeaches = array_filter($beaches, fn($b) => !empty($b['google_rating']));
$avgRating = !empty($ratedBeaches) ? array_sum(array_column($ratedBeaches, 'google_rating')) / count($ratedBeaches) : 0;

// Get tag distribution for these beaches
$tagCounts = [];
foreach ($beaches as $beach) {
    foreach ($beach['tags'] ?? [] as $tag) {
        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
    }
}
arsort($tagCounts);
$topTags = array_slice($tagCounts, 0, 8, true);

// Municipality distribution
$munCounts = [];
foreach ($beaches as $beach) {
    $mun = $beach['municipality'] ?? '';
    if ($mun) $munCounts[$mun] = ($munCounts[$mun] ?? 0) + 1;
}
arsort($munCounts);
$topMunicipalities = array_slice($munCounts, 0, 6, true);

$bodyVariant = 'collection-dark';
$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';

if ($redesignLayout) {
    $isEs = $lang === 'es';
    // Classic tag-distribution chips are non-linked spans — keep that (null url).
    $rdTagLinks = [];
    foreach ($topTags as $tag => $count) {
        if ($tag === $config['tag']) continue;
        $rdTagLinks[] = [ucwords(str_replace('-', ' ', $tag)), null, $count];
    }
    $rdMunicipalities = [];
    foreach ($topMunicipalities as $mun => $count) {
        $rdMunicipalities[] = [$mun, routeUrl('municipality', $lang, ['municipality' => strtolower(str_replace(' ', '-', $mun))]), $count];
    }
    $rdAnchors = [
        [$isEs ? 'Todas las Playas' : 'All Beaches', '#beaches'],
        [$isEs ? 'Por Municipio' : 'By Municipality', '#by-municipality'],
    ];
    if (!empty($tagFaqs)) {
        $rdAnchors[] = ['FAQ', '#faq'];
    }
    $rdStats = [[(string) $beachCount, $isEs ? 'playas' : 'beaches']];
    if ($avgRating > 0) {
        $rdStats[] = ['★ ' . number_format($avgRating, 1), $isEs ? 'promedio' : 'avg rating'];
    }
    $rdStats[] = [(string) count($munCounts), $isEs ? 'municipios' : 'municipalities'];
    $rdCrumbLabel = $config['type'] === 'amenity' ? getAmenityLabel($config['tag']) : getTagLabel($config['tag']);
    $listing = [
        'breadcrumbs' => [
            [$isEs ? 'Inicio' : 'Home', routeUrl('home', $lang)],
            [$isEs ? 'Playas' : 'Beaches', routeUrl('home', $lang) . '#beaches'],
            [$rdCrumbLabel, null],
        ],
        'eyebrow' => $config['type'] === 'amenity'
            ? ($isEs ? 'Playas por servicio' : 'Beaches by amenity')
            : ($isEs ? 'Playas por actividad' : 'Beaches by activity'),
        'h1' => $tagData['h1'],
        'intro' => [$tagData['intro']],
        'stats' => $rdStats,
        'anchors' => $rdAnchors,
        'tagLinks' => $rdTagLinks,
        'beachesHeading' => $isEs ? 'Todas las Playas' : 'All ' . $beachCount . ' Beaches',
        'beachesSub' => $isEs ? 'Ordenadas por calificación' : 'Sorted by rating',
        'beaches' => $beaches,
        'municipalities' => $rdMunicipalities,
        'faqs' => array_map(fn($f) => [$f['q'], $f['a']], $tagFaqs),
        'quizCta' => true,
    ];
    include APP_ROOT . '/templates/redesign/listing.php';
    include APP_ROOT . '/components/footer.php';
    return;
}
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-slate-900 via-slate-800 to-slate-700 text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h($tagData['h1']) ?></h1>
        <p class="text-lg md:text-xl text-white/90 max-w-3xl mx-auto mb-6"><?= h($tagData['intro']) ?></p>
        <div class="flex flex-wrap justify-center gap-4 text-sm text-white/80">
            <span class="bg-slate-700/50 px-3 py-1 rounded-full"><?= $beachCount ?> <?= $lang === 'es' ? 'playas' : 'beaches' ?></span>
            <?php if ($avgRating > 0): ?>
            <span class="bg-slate-700/50 px-3 py-1 rounded-full">★ <?= number_format($avgRating, 1) ?> <?= $lang === 'es' ? 'promedio' : 'avg rating' ?></span>
            <?php endif; ?>
            <span class="bg-slate-700/50 px-3 py-1 rounded-full"><?= count($munCounts) ?> <?= $lang === 'es' ? 'municipios' : 'municipalities' ?></span>
        </div>
    </div>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <a href="#beaches" class="text-amber-700 hover:underline"><?= $lang === 'es' ? 'Todas las Playas' : 'All Beaches' ?></a>
            <span class="text-gray-300">|</span>
            <a href="#by-municipality" class="text-amber-700 hover:underline"><?= $lang === 'es' ? 'Por Municipio' : 'By Municipality' ?></a>
            <?php if (!empty($tagFaqs)): ?>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline">FAQ</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Beach Cards Grid -->
<section id="beaches" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2"><?= $lang === 'es' ? 'Todas las Playas' : 'All ' . $beachCount . ' Beaches' ?></h2>
        <p class="text-gray-600 mb-8"><?= $lang === 'es' ? 'Ordenadas por calificación' : 'Sorted by rating' ?></p>

        <?php if (!empty($topTags)): ?>
        <div class="flex flex-wrap gap-2 mb-6">
            <?php foreach ($topTags as $tag => $count): ?>
                <?php if ($tag !== $config['tag']): ?>
                <span class="bg-white border border-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs">
                    <?= h(ucwords(str_replace('-', ' ', $tag))) ?> (<?= $count ?>)
                </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php $beachIdx = 0;
foreach (array_slice($beaches, 0, 30) as $beach):
$beachIdx++; ?>
            <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
               class="group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="<?= h(getBeachImageUrl($beach, 'medium')) ?>"
                         data-fallback-src="/images/beaches/placeholder-beach.webp"
                         alt="<?= h($beach['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="<?= $beachIdx <= 6 ? "eager" : "lazy" ?>" width="400" height="300">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 group-hover:text-amber-700 transition-colors"><?= h($beach['name']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= h($beach['municipality']) ?></p>
                    <div class="flex items-center gap-2 mt-2">
                        <?php if (!empty($beach['google_rating'])): ?>
                        <span class="text-sm text-amber-600">★ <?= number_format($beach['google_rating'], 1) ?></span>
                        <?php if (!empty($beach['google_review_count'])): ?>
                        <span class="text-xs text-gray-400">(<?= number_format($beach['google_review_count']) ?> <?= $lang === 'es' ? 'reseñas' : 'reviews' ?>)</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($beach['tags'])): ?>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <?php foreach (array_slice($beach['tags'], 0, 3) as $tag): ?>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"><?= h(ucwords(str_replace('-', ' ', $tag))) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($beachCount > 30): ?>
        <div id="more-beaches" class="mt-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($beaches, 30) as $beach): ?>
                <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
                   class="flex items-center gap-3 bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                    <img src="<?= h(getBeachImageUrl($beach, 'thumb')) ?>"
                         data-fallback-src="/images/beaches/placeholder-beach.webp"
                         alt="<?= h($beach['name']) ?>"
                         class="w-16 h-16 object-cover rounded-lg flex-shrink-0"
                         loading="lazy" width="64" height="64">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 truncate"><?= h($beach['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= h($beach['municipality']) ?></p>
                        <?php if (!empty($beach['google_rating'])): ?>
                        <span class="text-xs text-amber-600">★ <?= number_format($beach['google_rating'], 1) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- By Municipality Section -->
<section id="by-municipality" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">
            <?= $lang === 'es' ? 'Por Municipio' : 'By Municipality' ?>
        </h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($topMunicipalities as $mun => $count): ?>
            <a href="<?= h(routeUrl('municipality', $lang, ['municipality' => strtolower(str_replace(' ', '-', $mun))])) ?>"
               class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow flex justify-between items-center">
                <span class="font-medium text-gray-900"><?= h($mun) ?></span>
                <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded"><?= $count ?> <?= $lang === 'es' ? 'playas' : 'beaches' ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($tagFaqs)): ?>
<!-- FAQ Section -->
<section id="faq" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= $lang === 'es' ? 'Preguntas Frecuentes' : 'Frequently Asked Questions' ?>
        </h2>
        <div class="space-y-4">
            <?php foreach ($tagFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['q']) ?>
                    <span class="text-amber-700 group-open:rotate-180 transition-transform flex-shrink-0 ml-4">▼</span>
                </summary>
                <div class="px-6 pb-6 text-gray-700 leading-relaxed">
                    <?= h($faq['a']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="py-12 bg-sunset-400 text-ocean-900">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">
            <?= $lang === 'es' ? '¿No sabes cuál playa es para ti?' : 'Not sure which beach is right for you?' ?>
        </h2>
        <p class="text-lg opacity-90 mb-6">
            <?= $lang === 'es' ? 'Toma nuestro quiz de 60 segundos y te recomendaremos playas perfectas para ti.' : 'Take our 60-second quiz and we\'ll recommend the perfect beaches for you.' ?>
        </p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-white text-amber-700 hover:bg-slate-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            <?= $lang === 'es' ? 'Tomar el Quiz' : 'Take the Beach Quiz' ?>
        </a>
    </div>
</section>

<?php include APP_ROOT . '/components/footer.php'; ?>
