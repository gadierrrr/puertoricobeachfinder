#!/usr/bin/env php
<?php
/**
 * Generate content sections from existing structured beach data.
 * Uses templates + existing tags/amenities/features/tips/field data
 * to produce 6 content sections per beach without external API calls.
 */

if (php_sapi_name() !== 'cli') die("CLI only.\n");

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/constants.php';

// Municipality context for history/nearby sections
$MUNICIPALITY_CONTEXT = [
    'Aguada' => ['region' => 'west', 'context' => 'western coast municipality where Columbus allegedly first landed in 1493', 'nearby_town' => 'Rincón', 'landmark' => 'Parque Colón and the Discovery Monument'],
    'Aguadilla' => ['region' => 'northwest', 'context' => 'northwest coast city with a rich military history tied to Ramey Air Force Base', 'nearby_town' => 'Isabela', 'landmark' => 'Crash Boat Beach and the Punta Borinquen Lighthouse'],
    'Aguas Buenas' => ['region' => 'interior', 'context' => 'mountain municipality in the central highlands known as the City of Springs', 'nearby_town' => 'Caguas', 'landmark' => 'Las Cuevas del Río Cañas cave system'],
    'Aibonito' => ['region' => 'interior', 'context' => 'highest municipality in Puerto Rico, situated in the Cordillera Central', 'nearby_town' => 'Barranquitas', 'landmark' => 'San Cristóbal Canyon'],
    'Añasco' => ['region' => 'west', 'context' => 'western agricultural municipality near the mouth of the Río Grande de Añasco', 'nearby_town' => 'Mayagüez', 'landmark' => 'Añasco River delta wetlands'],
    'Arecibo' => ['region' => 'north', 'context' => 'north coast municipality known for the former Arecibo Observatory and limestone karst landscape', 'nearby_town' => 'Barceloneta', 'landmark' => 'Arecibo Lighthouse and Historical Park'],
    'Arroyo' => ['region' => 'south', 'context' => 'southern coast municipality historically important as a sugar port', 'nearby_town' => 'Guayama', 'landmark' => 'Tren del Sur historic railway and Punta Guilarte'],
    'Barceloneta' => ['region' => 'north', 'context' => 'north coast municipality known for pharmaceutical manufacturing and its beach festivals', 'nearby_town' => 'Arecibo', 'landmark' => 'Barceloneta beach boardwalk area'],
    'Cabo Rojo' => ['region' => 'southwest', 'context' => 'southwestern municipality famous for salt flats, the Los Morrillos Lighthouse, and spectacular sunsets', 'nearby_town' => 'Lajas', 'landmark' => 'Los Morrillos Lighthouse and Cabo Rojo Salt Flats'],
    'Caguas' => ['region' => 'interior', 'context' => 'inland city in the Turabo Valley, the heart of the Criollo Corridor', 'nearby_town' => 'Gurabo', 'landmark' => 'Jardín Botánico y Cultural de Caguas'],
    'Camuy' => ['region' => 'north', 'context' => 'north coast municipality known for the Río Camuy Cave Park, one of the largest cave systems in the Western Hemisphere', 'nearby_town' => 'Hatillo', 'landmark' => 'Río Camuy Cave Park'],
    'Canóvanas' => ['region' => 'northeast', 'context' => 'northeastern municipality near the foothills of El Yunque', 'nearby_town' => 'Loíza', 'landmark' => 'the former El Comandante racetrack area'],
    'Carolina' => ['region' => 'northeast', 'context' => 'northeastern municipality home to Luis Muñoz Marín International Airport and Isla Verde beach strip', 'nearby_town' => 'San Juan', 'landmark' => 'Isla Verde beach and the Piñones nature boardwalk'],
    'Ceiba' => ['region' => 'east', 'context' => 'eastern municipality on the former Roosevelt Roads Naval Station grounds, gateway to Vieques and Culebra ferries', 'nearby_town' => 'Fajardo', 'landmark' => 'the Ceiba ferry terminal to Vieques and Culebra'],
    'Coamo' => ['region' => 'south-interior', 'context' => 'one of the oldest settlements in Puerto Rico, known for its thermal springs', 'nearby_town' => 'Salinas', 'landmark' => 'the Coamo Hot Springs (Baños de Coamo)'],
    'Culebra' => ['region' => 'island', 'context' => 'small island municipality 17 miles east of the mainland, accessible by ferry from Ceiba or small plane', 'nearby_town' => 'Dewey (the only town)', 'landmark' => 'Flamenco Beach and the Culebra National Wildlife Refuge'],
    'Dorado' => ['region' => 'north', 'context' => 'north coast municipality transformed from plantation land to a resort destination by Laurance Rockefeller in the 1950s', 'nearby_town' => 'Toa Baja', 'landmark' => 'Dorado Beach Resort and the natural ocean pool Ojo del Buey'],
    'Fajardo' => ['region' => 'east', 'context' => 'eastern coastal municipality, a marina town and gateway to offshore cays and bioluminescent Laguna Grande', 'nearby_town' => 'Luquillo', 'landmark' => 'Las Cabezas de San Juan Nature Reserve and marina district'],
    'Guánica' => ['region' => 'south', 'context' => 'southern municipality home to the Guánica Dry Forest, a UNESCO Biosphere Reserve, and site of the 1898 US invasion', 'nearby_town' => 'Yauco', 'landmark' => 'Bosque Seco de Guánica (Guánica Dry Forest)'],
    'Guayanilla' => ['region' => 'south', 'context' => 'southern coast municipality between Ponce and Yauco with Caribbean-facing shores', 'nearby_town' => 'Peñuelas', 'landmark' => 'the Guayanilla Bay area and its offshore keys'],
    'Guayama' => ['region' => 'south', 'context' => 'southern coast municipality historically known as the City of Witches (Ciudad Bruja)', 'nearby_town' => 'Arroyo', 'landmark' => 'the Aguirre Sugar Central ruins and Jobos Bay Reserve'],
    'Hatillo' => ['region' => 'north', 'context' => 'north coast municipality famous for the annual Festival de las Máscaras (Mask Festival)', 'nearby_town' => 'Camuy', 'landmark' => 'Cueva del Indio archaeological site'],
    'Humacao' => ['region' => 'east', 'context' => 'eastern coast municipality with the Humacao Nature Reserve and Palmas del Mar resort', 'nearby_town' => 'Naguabo', 'landmark' => 'Humacao Wildlife Refuge and Palmas del Mar'],
    'Isabela' => ['region' => 'northwest', 'context' => 'northwest coast municipality with dramatic limestone cliffs and world-class surf breaks', 'nearby_town' => 'Aguadilla', 'landmark' => 'the Guajataca tunnel and Jobos Beach'],
    'Jayuya' => ['region' => 'interior', 'context' => 'mountain municipality in the heart of the Cordillera Central, center of Taíno heritage', 'nearby_town' => 'Utuado', 'landmark' => 'Cerro de Punta (highest peak) and the Taíno Ceremonial Center'],
    'Juana Díaz' => ['region' => 'south', 'context' => 'southern municipality known for its Three Kings Day celebrations', 'nearby_town' => 'Ponce', 'landmark' => 'the annual Festival de Reyes Magos'],
    'Lajas' => ['region' => 'southwest', 'context' => 'southwestern municipality on the Lajas Valley, gateway to La Parguera bioluminescent bay', 'nearby_town' => 'Cabo Rojo', 'landmark' => 'La Parguera bioluminescent bay and mangrove channels'],
    'Las Piedras' => ['region' => 'east-interior', 'context' => 'eastern interior municipality with archaeological significance', 'nearby_town' => 'Humacao', 'landmark' => 'the petroglyphs and Taíno archaeological sites'],
    'Loíza' => ['region' => 'northeast', 'context' => 'northeast coast municipality, the heart of Afro-Puerto Rican culture, known for bomba music and vejigante traditions', 'nearby_town' => 'Carolina', 'landmark' => 'the Piñones coastal boardwalk and mangrove forest'],
    'Luquillo' => ['region' => 'east', 'context' => 'eastern coast municipality famous for its beachfront food kiosks and proximity to El Yunque National Forest', 'nearby_town' => 'Fajardo', 'landmark' => 'the Luquillo Beach kiosks and La Pared surf spot'],
    'Manatí' => ['region' => 'north', 'context' => 'north coast municipality known for its limestone coastline and natural pools', 'nearby_town' => 'Barceloneta', 'landmark' => 'Mar Chiquita Beach (natural half-moon cove)'],
    'Maunabo' => ['region' => 'southeast', 'context' => 'southeastern corner municipality where the Caribbean meets the Atlantic', 'nearby_town' => 'Patillas', 'landmark' => 'Punta Tuna Lighthouse, one of three operating lighthouses in Puerto Rico'],
    'Mayagüez' => ['region' => 'west', 'context' => 'western coast municipality, the third-largest city, home to the University of Puerto Rico at Mayagüez', 'nearby_town' => 'Añasco', 'landmark' => 'Plaza Colón and the Tropical Agriculture Research Station'],
    'Naguabo' => ['region' => 'east', 'context' => 'eastern coast municipality with views of offshore cays and Monkey Island (Cayo Santiago)', 'nearby_town' => 'Humacao', 'landmark' => 'Cayo Santiago (Monkey Island) visible offshore'],
    'Patillas' => ['region' => 'southeast', 'context' => 'southeastern municipality known as the Emerald of the South with a mountain reservoir', 'nearby_town' => 'Maunabo', 'landmark' => 'Lake Patillas reservoir and its surrounding trails'],
    'Peñuelas' => ['region' => 'south', 'context' => 'southern coast municipality between Ponce and Guayanilla', 'nearby_town' => 'Guayanilla', 'landmark' => 'the Peñuelas Bay and its mangrove systems'],
    'Ponce' => ['region' => 'south', 'context' => 'southern coast municipality, the second-largest city, known as the Pearl of the South with rich architecture', 'nearby_town' => 'Juana Díaz', 'landmark' => 'Museo de Arte de Ponce and the Parque de Bombas firehouse'],
    'Quebradillas' => ['region' => 'northwest', 'context' => 'northwest coast municipality with dramatic seaside cliffs and the Guajataca area', 'nearby_town' => 'Isabela', 'landmark' => 'the Guajataca Tunnel and El Merendero cliff-top lookout'],
    'Rincón' => ['region' => 'west', 'context' => 'western municipality known as the surfing capital of Puerto Rico, host of the 1968 World Surfing Championship', 'nearby_town' => 'Aguada', 'landmark' => 'Punta Higuera Lighthouse and the Domes surf break'],
    'Salinas' => ['region' => 'south', 'context' => 'southern coast municipality renowned for seafood and the famous mojo isleño sauce', 'nearby_town' => 'Guayama', 'landmark' => 'the Salinas boardwalk seafood restaurants'],
    'San Juan' => ['region' => 'north', 'context' => 'capital city on the north coast, founded in 1521, with historic Old San Juan and Condado beach districts', 'nearby_town' => 'Carolina', 'landmark' => 'El Morro and San Cristóbal fortresses, Condado strip'],
    'Santa Isabel' => ['region' => 'south', 'context' => 'southern coast agricultural municipality with calm Caribbean shores', 'nearby_town' => 'Salinas', 'landmark' => 'the sugar heritage sites and calm southern beaches'],
    'Toa Baja' => ['region' => 'north', 'context' => 'north coast municipality adjacent to San Juan, with mangrove-bordered coastline', 'nearby_town' => 'Dorado', 'landmark' => 'Punta Salinas area and its coastal wetlands'],
    'Toa Alta' => ['region' => 'north-interior', 'context' => 'northern interior municipality in the metropolitan area foothills', 'nearby_town' => 'Toa Baja', 'landmark' => 'the historic churches and plaza area'],
    'Vega Alta' => ['region' => 'north', 'context' => 'north coast municipality with Atlantic-facing beaches and a growing coastal community', 'nearby_town' => 'Vega Baja', 'landmark' => 'Cerro Gordo Beach and its camping area'],
    'Vega Baja' => ['region' => 'north', 'context' => 'north coast municipality known as the Melting Pot City, with popular local beaches', 'nearby_town' => 'Vega Alta', 'landmark' => 'Puerto Nuevo Beach and the Tortuguero Lagoon Nature Reserve'],
    'Vieques' => ['region' => 'island', 'context' => 'island municipality 8 miles off the southeast coast, formerly used by the US Navy, now a nature and beach destination', 'nearby_town' => 'Isabel Segunda (main town)', 'landmark' => 'Mosquito Bay (brightest bioluminescent bay in the world) and Sun Bay'],
    'Villalba' => ['region' => 'interior', 'context' => 'mountain municipality in the Cordillera Central known for its reservoir lakes', 'nearby_town' => 'Orocovis', 'landmark' => 'Toro Negro State Forest and its peaks'],
    'Yabucoa' => ['region' => 'southeast', 'context' => 'southeastern municipality where the sunrise first hits Puerto Rico, known as the Sugar City', 'nearby_town' => 'Maunabo', 'landmark' => 'the Yabucoa Valley and Punta Tuna area'],
    'Yauco' => ['region' => 'southwest', 'context' => 'southwestern municipality known as the Coffee Town for its historic coffee production', 'nearby_town' => 'Guánica', 'landmark' => 'the painted streets of Yauco and coffee haciendas'],
];

// Coast characteristics
$COAST_INFO = [
    'north' => 'This stretch of Puerto Rico\'s north coast faces the Atlantic Ocean, where trade winds generate consistent swells and currents that shape the shoreline. The northern coast experiences more wave energy than the south, with winter months bringing larger swells from the north Atlantic.',
    'south' => 'Situated on Puerto Rico\'s southern Caribbean coast, this area benefits from calmer waters sheltered from Atlantic swells by the island\'s central mountain range. The Caribbean side tends to be warmer and more tranquil, with better underwater visibility for much of the year.',
    'east' => 'Located on Puerto Rico\'s eastern shore, this area sits where the Atlantic and Caribbean converge. The east coast serves as a jumping-off point to offshore islands and cays, with trade winds providing steady breezes.',
    'west' => 'On Puerto Rico\'s western coast facing the Mona Passage, this area is renowned for dramatic sunsets and surf culture. The west coast catches swells from multiple directions, and the warm Caribbean influence keeps waters comfortable year-round.',
    'northwest' => 'On the northwest corner of Puerto Rico, this area catches both north Atlantic swells and west-facing Mona Passage energy. The limestone coastline creates dramatic cliff formations and natural pools carved by centuries of wave action.',
    'northeast' => 'Located on Puerto Rico\'s northeastern coast, this area benefits from proximity to El Yunque\'s rainfall and lush vegetation that extends to the shoreline. Atlantic swells and trade winds shape the beach conditions.',
    'southwest' => 'On the southwestern tip of Puerto Rico, this area faces the Caribbean Sea with calm conditions and warm waters. The dry climate creates a landscape distinct from the rest of the island, with salt flats and scrubland meeting the shore.',
    'southeast' => 'At the southeastern corner where the Caribbean and Atlantic meet, this area has a character all its own. The mountain backdrop of the Sierra de Pandura influences local weather patterns.',
    'island' => 'As an offshore island municipality, this area offers a different pace from mainland Puerto Rico. The surrounding waters are influenced by both Atlantic and Caribbean currents, and the relative isolation has preserved the natural coastal environment.',
    'interior' => 'Though this municipality sits inland, nearby coastal areas are within reach, connecting mountain communities to the shore.',
    'south-interior' => 'Though inland, this municipality has historical connections to the southern coast through trade and sugar routes.',
    'north-interior' => 'Though primarily inland, this municipality\'s northern position means coastal beaches are a short drive away.',
    'east-interior' => 'Though primarily inland, this municipality\'s eastern position means coastal beaches and offshore cays are within easy reach.',
];

// Season templates by coast
$SEASON_INFO = [
    'north' => 'Winter months (December through March) bring the largest north Atlantic swells, making conditions more dynamic. Summer (June through August) typically sees calmer seas and warmer water, though afternoon thunderstorms are common. The dry season from January to April generally offers the most reliable beach weather.',
    'south' => 'The southern coast enjoys calmer conditions year-round compared to the north. December through April is the driest period with comfortable temperatures. Summer brings slightly warmer waters and occasional brief afternoon showers. The protected southern exposure means this beach is often swimmable even when north coast beaches are rough.',
    'east' => 'Trade winds blow steadily from the northeast, keeping temperatures comfortable but sometimes creating chop. The dry season (December through April) offers the most predictable conditions. Summer brings warmer water but more frequent afternoon rain showers, typically short-lived.',
    'west' => 'The west coast comes alive with surf from October through April when north swells wrap around the island. Summer months offer calmer waters better suited for swimming and snorkeling. Sunsets are spectacular year-round, but the clear skies of the dry season (January through April) provide the most vivid displays.',
    'island' => 'Island weather tends to be drier and sunnier than the mainland. High season (December through April) brings the best conditions but also the most visitors. The shoulder months of May and November offer a balance of good weather and fewer crowds. Ferry schedules may be affected during rough weather in winter.',
];

$TAG_ACTIVITIES = [
    'surfing' => 'surfing and bodyboarding',
    'snorkeling' => 'snorkeling and exploring underwater marine life',
    'diving' => 'scuba diving and underwater exploration',
    'swimming' => 'swimming and wading',
    'fishing' => 'shore fishing and casting',
    'calm-waters' => 'gentle swimming and floating in calm conditions',
    'family-friendly' => 'family activities and safe play for children',
    'scenic' => 'photography and scenic appreciation',
    'secluded' => 'solitude and quiet relaxation away from crowds',
    'popular' => 'socializing and experiencing a lively beach atmosphere',
    'camping' => 'overnight camping along the shore',
    'wildlife' => 'wildlife observation and nature study',
];

$TAG_GEAR = [
    'surfing' => 'a surfboard (shortboard or longboard depending on conditions), rash guard, surf wax, and reef boots if the bottom is rocky',
    'snorkeling' => 'a snorkel set (mask, snorkel, fins), reef-safe sunscreen, and an underwater camera if you have one',
    'diving' => 'your dive certification card and personal dive gear, or contact a local dive shop for rental equipment',
    'swimming' => 'a comfortable swimsuit, goggles, and a towel',
    'fishing' => 'fishing rod, tackle, bait, a cooler for your catch, and a valid Puerto Rico fishing license',
    'calm-waters' => 'a float or inflatable for lounging on the calm surface, plus swim gear for the whole family',
    'family-friendly' => 'sand toys, a beach tent or umbrella for shade, snacks, and plenty of water for the kids',
    'scenic' => 'a camera with extra battery, a tripod for sunset shots, and binoculars for coastal views',
    'secluded' => 'all supplies you will need including food, water, and a first aid kit, as amenities are likely unavailable',
    'camping' => 'a tent, sleeping bag, camping stove, headlamp, and insect repellent',
];

$AMENITY_INFO = [
    'parking' => 'Parking is available on-site.',
    'restrooms' => 'Restroom facilities are provided.',
    'food' => 'Food vendors or restaurants are nearby.',
    'picnic-areas' => 'Designated picnic areas with tables are available.',
    'shade-structures' => 'Shade structures or palapas offer relief from the sun.',
    'lifeguards' => 'Lifeguards are on duty during posted hours.',
    'accessibility' => 'The beach has accessibility features for visitors with mobility needs.',
    'showers' => 'Outdoor showers are available for rinsing off.',
    'camping' => 'Camping is permitted in designated areas.',
    'water-sports-rentals' => 'Water sports equipment rentals are available.',
];

// ---------- MAIN ----------

$dryRun = in_array('--dry-run', $argv);
$limit = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) $limit = (int)$m[1];
}

$beaches = query("
    SELECT b.*,
        (SELECT GROUP_CONCAT(tag) FROM beach_tags WHERE beach_id = b.id) as tags,
        (SELECT GROUP_CONCAT(amenity) FROM beach_amenities WHERE beach_id = b.id) as amenities
    FROM beaches b
    WHERE b.id NOT IN (SELECT DISTINCT beach_id FROM beach_content_sections)
    ORDER BY b.name
" . ($limit ? " LIMIT {$limit}" : ""));

echo "Generating content sections for " . count($beaches) . " beaches...\n";

$db = getDB();
$count = 0;
$errors = 0;

foreach ($beaches as $beach) {
    $id = $beach['id'];
    $name = $beach['name'];
    $muni = $beach['municipality'];
    $desc = $beach['description'] ?? '';
    $tags = $beach['tags'] ? explode(',', $beach['tags']) : [];
    $amenities = $beach['amenities'] ? explode(',', $beach['amenities']) : [];

    // Get features and tips
    $features = query("SELECT title, description FROM beach_features WHERE beach_id = ? ORDER BY position", [$id]);
    $tips = query("SELECT category, tip FROM beach_tips WHERE beach_id = ? ORDER BY position", [$id]);

    $muniData = $MUNICIPALITY_CONTEXT[$muni] ?? ['region' => 'north', 'context' => "{$muni} municipality in Puerto Rico", 'nearby_town' => 'nearby towns', 'landmark' => 'local attractions'];
    $region = $muniData['region'];
    $coastInfo = $COAST_INFO[$region] ?? $COAST_INFO['north'];
    $seasonBase = $SEASON_INFO[$region] ?? $SEASON_INFO[$region] ?? $SEASON_INFO['north'];
    if (isset($SEASON_INFO[$region])) {
        $seasonBase = $SEASON_INFO[$region];
    } else {
        // Map sub-regions to main season info
        $seasonMap = ['northwest' => 'west', 'northeast' => 'east', 'southwest' => 'west', 'southeast' => 'east', 'south-interior' => 'south', 'north-interior' => 'north', 'east-interior' => 'east'];
        $seasonBase = $SEASON_INFO[$seasonMap[$region] ?? 'north'] ?? $SEASON_INFO['north'];
    }

    // Generate 6 sections
    $sections = [];

    // 1. HISTORY
    $featureText = '';
    foreach ($features as $f) {
        $featureText .= "\n\n" . $f['description'];
    }
    $historyContent = "{$name} is located in the municipality of {$muni}, {$muniData['context']}. {$coastInfo}\n\n{$desc}{$featureText}\n\nThe municipality of {$muni} has deep roots in Puerto Rico's history, and beaches like {$name} reflect the ongoing relationship between coastal communities and the sea. Whether visited by local families on weekends or travelers discovering the area for the first time, this stretch of shoreline carries the character of its surroundings.";
    $sections[] = ['type' => 'history', 'heading' => 'History & Background', 'content' => $historyContent];

    // 2. BEST TIME
    $bestTimeField = $beach['best_time'] ?? '';
    $bestTimeContent = $seasonBase;
    if ($bestTimeField) {
        $bestTimeContent .= "\n\n" . $bestTimeField;
    }
    $timingTip = '';
    foreach ($tips as $t) {
        if (stripos($t['category'], 'Timing') !== false || stripos($t['category'], 'Best Time') !== false) {
            $timingTip = $t['tip'];
            break;
        }
    }
    if ($timingTip) {
        $bestTimeContent .= "\n\n" . $timingTip;
    }
    if (in_array('popular', $tags)) {
        $bestTimeContent .= "\n\nThis is a well-visited beach, so arriving early on weekends is recommended to secure a good spot. Weekday visits tend to be considerably less crowded.";
    }
    if (in_array('secluded', $tags)) {
        $bestTimeContent .= "\n\nDue to its secluded nature, this beach rarely experiences significant crowding, even on weekends and holidays.";
    }
    $sections[] = ['type' => 'best_time', 'heading' => 'Best Time to Visit', 'content' => $bestTimeContent];

    // 3. GETTING THERE
    $parkingField = $beach['parking_details'] ?? '';
    $accessLabel = $beach['access_label'] ?? '';

    // Regional driving directions from San Juan
    $drivingDirs = [
        'north' => "From San Juan, head west on Highway 22 (toll road) toward {$muni}. The drive takes roughly 30 minutes to 1.5 hours depending on distance. GPS navigation is recommended as coastal road signage can be minimal.",
        'south' => "From San Juan, take Highway 52 south through the central mountains toward Ponce, then connect to Highway 2 or local routes toward {$muni}. The drive takes approximately 1.5 to 2 hours. The toll expressway through the mountains is the fastest route.",
        'east' => "From San Juan, take Highway 26 east to Highway 66, continuing east on Route 3 toward {$muni}. The drive takes 45 minutes to 1.5 hours. The eastern highway passes through Canóvanas and the El Yunque foothills.",
        'west' => "From San Juan, take Highway 22 west, which becomes Highway 2 past Arecibo, continuing toward the western coast and {$muni}. Allow 2 to 2.5 hours for the drive. The toll expressway covers the first half quickly.",
        'northwest' => "From San Juan, take Highway 22 west toward Arecibo, then continue on Highway 2 or Route 119 to reach {$muni}. The drive takes approximately 1.5 to 2 hours via the toll expressway.",
        'northeast' => "From San Juan, head east on Highway 26/66 and continue on Route 3 toward {$muni}. The drive takes approximately 30 minutes to 1 hour depending on traffic.",
        'southwest' => "From San Juan, take Highway 52 south to Ponce, then Highway 2 west toward {$muni}. Total drive time is approximately 2 to 2.5 hours. Alternatively, take Highway 22 west and cut south, though this can take longer.",
        'southeast' => "From San Juan, take Highway 52 south to Cayey, then Route 53 east toward {$muni}. The drive takes approximately 1.5 to 2 hours through the scenic mountain corridor.",
        'island' => "Reaching {$muni} requires taking the ferry from the Ceiba terminal (formerly Fajardo) or booking a small commuter flight from Isla Grande or Ceiba airports. The ferry ride takes about 30 minutes to Culebra or 1 hour to Vieques. Book tickets in advance, especially on weekends and holidays, as ferries sell out. Plan island transportation ahead of time since rental cars and taxis have limited availability.",
        'interior' => "From San Juan, take the appropriate highway south into the central mountain region toward {$muni}. Drive times vary from 45 minutes to 1.5 hours depending on the specific location.",
        'south-interior' => "From San Juan, take Highway 52 south through the mountains toward {$muni}. The drive takes approximately 1 to 1.5 hours.",
        'north-interior' => "From San Juan, take the appropriate route south into the foothills toward {$muni}. The drive typically takes 30 to 45 minutes.",
        'east-interior' => "From San Juan, head east on Highway 30 or Route 52 south and connect eastward toward {$muni}. The drive takes approximately 45 minutes to 1 hour.",
    ];

    $gettingThereContent = "{$name} is located in {$muni} on Puerto Rico's " . ($region === 'island' ? 'offshore islands' : "{$region} coast") . ". " . ($drivingDirs[$region] ?? $drivingDirs['north']);

    if ($accessLabel) {
        $accessMap = [
            'short path' => "\n\nOnce you arrive, access is straightforward with a short path from the parking area to the sand.",
            '10-min walk' => "\n\nReaching the beach requires approximately a 10-minute walk from the nearest parking area. Wear comfortable shoes for the approach.",
            'moderate hike' => "\n\nGetting to the beach involves a moderate hike, so wear appropriate footwear and bring water for the walk.",
            'difficult hike' => "\n\nAccess requires a challenging hike over uneven terrain. Sturdy hiking shoes are essential, and the trek is not suitable for those with mobility limitations. Allow extra time and energy for the return trip.",
            'road & parking' => "\n\nThe beach is accessible by road with nearby parking, making it easy to reach once you arrive in the area.",
            'road & short trail' => "\n\nDrive to the area and follow a short trail to reach the beach. The path is generally manageable in regular shoes.",
            'hike via trail' => "\n\nThe beach is reached via a hiking trail. Wear proper footwear and allow extra time for the walk in and out.",
        ];
        $gettingThereContent .= ($accessMap[$accessLabel] ?? "\n\nAccess is via {$accessLabel}.");
    }
    if ($parkingField) {
        $gettingThereContent .= "\n\n" . $parkingField;
    } elseif (in_array('parking', $amenities)) {
        $gettingThereContent .= "\n\nParking is available near the beach. Arrive early on weekends to secure a spot, as lots can fill by mid-morning during peak season.";
    } else {
        $gettingThereContent .= "\n\nParking options are limited in this area. Look for informal roadside parking or check with locals for the best places to leave your vehicle. Avoid blocking residential driveways or access roads.";
    }
    $parkingTip = '';
    foreach ($tips as $t) {
        if (stripos($t['category'], 'Parking') !== false || stripos($t['category'], 'Access') !== false) {
            $parkingTip = $t['tip'];
            break;
        }
    }
    if ($parkingTip) {
        $gettingThereContent .= "\n\n" . $parkingTip;
    }
    $gettingThereContent .= "\n\nA rental car is the most practical way to explore beaches outside the San Juan metro area. Major rental agencies operate from the airport and hotel districts. Be aware that GPS coordinates are more reliable than street addresses for finding beaches in Puerto Rico, as signage varies.";
    $sections[] = ['type' => 'getting_there', 'heading' => 'Getting There', 'content' => $gettingThereContent];

    // 4. WHAT TO BRING
    $bringItems = [];
    foreach ($tags as $tag) {
        if (isset($TAG_GEAR[$tag])) $bringItems[] = $TAG_GEAR[$tag];
    }
    $bringContent = "What you pack for {$name} depends on how you plan to spend your time.";
    if (!empty($bringItems)) {
        $bringContent .= " Based on the activities available here, consider bringing " . implode('. Also pack ', array_slice($bringItems, 0, 3)) . ".";
    }
    $bringContent .= "\n\nRegardless of your planned activities, essentials include reef-safe sunscreen (SPF 30 or higher), a reusable water bottle, and a hat for sun protection. Puerto Rico's tropical sun is intense, especially between 10 AM and 2 PM.";

    // Amenity-based additions
    if (!in_array('food', $amenities)) {
        $bringContent .= "\n\nThere are no food vendors at or near this beach, so pack your own meals, snacks, and plenty of water in a cooler.";
    } else {
        $bringContent .= "\n\nFood is available nearby, but bringing your own water and snacks is still recommended.";
    }
    if (!in_array('shade-structures', $amenities)) {
        $bringContent .= " A portable beach umbrella or pop-up shade tent is highly recommended, as natural shade may be limited.";
    }

    $safetyField = $beach['safety_info'] ?? '';
    if ($safetyField) {
        $bringContent .= "\n\n" . $safetyField;
    }
    $equipTip = '';
    foreach ($tips as $t) {
        if (stripos($t['category'], 'Equipment') !== false || stripos($t['category'], 'Gear') !== false || stripos($t['category'], 'Footwear') !== false || stripos($t['category'], 'Preparation') !== false) {
            $equipTip = $t['tip'];
            break;
        }
    }
    if ($equipTip) {
        $bringContent .= "\n\n" . $equipTip;
    }
    $sections[] = ['type' => 'what_to_bring', 'heading' => 'What to Bring', 'content' => $bringContent];

    // 5. NEARBY
    $nearbyContent = "{$name} is situated in {$muni}, which offers several attractions beyond the beach itself.";
    if ($muniData['landmark'] !== 'local attractions') {
        $nearbyContent .= " One of the area's main draws is {$muniData['landmark']}, well worth a visit if you have extra time.";
    }
    if ($muniData['nearby_town'] && $muniData['nearby_town'] !== 'nearby towns') {
        $nearbyContent .= " The neighboring area of {$muniData['nearby_town']} is also worth exploring and is a short drive away.";
    }
    $nearbyContent .= "\n\nFor dining, look for local restaurants and roadside kiosks (chinchorros) serving traditional Puerto Rican fare. Coastal towns typically offer fresh seafood, mofongo (mashed plantain with garlic), and tostones (fried plantain slices). Many beach areas have informal food stalls that appear on weekends, selling empanadillas, bacalaítos (codfish fritters), and piraguas (shaved ice with fruit syrup) at affordable prices. Ask locals for their favorite spot — the best food is often at the least conspicuous establishments.";
    if (in_array('food', $amenities)) {
        $nearbyContent .= " Food vendors are also available at or near the beach itself, so you can grab a bite without leaving the sand.";
    }
    // Add other beach references from same municipality
    $otherBeaches = query("SELECT name FROM beaches WHERE municipality = ? AND id <> ? ORDER BY RANDOM() LIMIT 3", [$muni, $id]);
    if (!empty($otherBeaches)) {
        $names = array_map(fn($b) => $b['name'], $otherBeaches);
        if (count($names) >= 2) {
            $last = array_pop($names);
            $nearbyContent .= "\n\nOther beaches in the {$muni} area include " . implode(', ', $names) . " and {$last}, each with a different character worth exploring if you have time for beach hopping.";
        } else {
            $nearbyContent .= "\n\n" . $names[0] . " is another beach in the {$muni} area worth visiting.";
        }
    }
    $nearbyContent .= "\n\nThe {$muni} town center typically features a central plaza with a church, local shops, and cafes — a good place to experience everyday Puerto Rican town life. Many towns hold weekend markets or festivals throughout the year, particularly during patron saint celebrations (fiestas patronales) which feature live music, food, and cultural performances.";
    $sections[] = ['type' => 'nearby', 'heading' => 'Nearby Attractions', 'content' => $nearbyContent];

    // 6. LOCAL TIPS
    $localTipsContent = "Here are some practical tips for making the most of your visit to {$name}:";
    $usedTips = [];
    foreach ($tips as $t) {
        // Skip tips already used in other sections
        if ($t['tip'] === $timingTip || $t['tip'] === $parkingTip || $t['tip'] === $equipTip) continue;
        $usedTips[] = $t['tip'];
    }
    if (!empty($usedTips)) {
        foreach ($usedTips as $tip) {
            $localTipsContent .= "\n\n" . $tip;
        }
    }
    $localTipsContent .= "\n\nAs with all Puerto Rico beaches, the beach zone up to the high-tide mark is public land by law, regardless of any adjacent private property or signage suggesting otherwise. You have the legal right to access and enjoy any beach in Puerto Rico.";
    $localTipsContent .= "\n\nPack out all trash and leave the beach as you found it. Puerto Rico's coastal ecosystems are under pressure from development and climate change, and responsible visitors make a real difference. Between April and November, sea turtles may nest on sandy beaches — if you spot a nest or hatchlings, keep your distance and report it to the DRNA (Department of Natural Resources).";
    if (in_array('surfing', $tags)) {
        $localTipsContent .= "\n\nIf you are new to surfing in Puerto Rico, consider hiring a local instructor who knows the specific conditions, reef layout, and hazards at this break. Surf etiquette applies: do not drop in on other surfers, and give right of way to the person closest to the peak of the wave.";
    }
    if (in_array('snorkeling', $tags)) {
        $localTipsContent .= "\n\nWhen snorkeling, avoid touching or standing on coral formations, as they are fragile and legally protected under both federal and Puerto Rico law. Maintain neutral buoyancy and keep your fins from scraping the bottom.";
    }
    if (in_array('calm-waters', $tags)) {
        $localTipsContent .= "\n\nWhile the calm waters here are generally safe, ocean conditions can change quickly. Keep an eye on weather forecasts and be aware that even sheltered areas can develop currents after storms or during unusual tidal patterns.";
    }
    $localTipsContent .= "\n\nSpanish is the primary language in most areas outside San Juan's tourist zones. Learning a few phrases like '¿Dónde está la playa?' (Where is the beach?) and 'Gracias' (Thank you) goes a long way with locals. Puerto Ricans are generally warm and helpful toward visitors who show respect for the culture and environment.";
    $sections[] = ['type' => 'local_tips', 'heading' => 'Local Tips', 'content' => $localTipsContent];

    // Insert into database
    if (!$dryRun) {
        $db->exec('BEGIN TRANSACTION');
        try {
            $order = 1;
            foreach ($sections as $s) {
                $sid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                $wordCount = str_word_count($s['content']);
                execute(
                    "INSERT INTO beach_content_sections (id, beach_id, section_type, heading, content, word_count, display_order, status, generated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', datetime('now'))",
                    [$sid, $id, $s['type'], $s['heading'], $s['content'], $wordCount, $order]
                );
                $order++;
            }
            $db->exec('COMMIT');
            $count++;
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $errors++;
            echo "  ERROR [{$name}]: {$e->getMessage()}\n";
        }
    } else {
        $count++;
    }

    if ($count % 50 === 0) {
        echo "  Processed {$count}/" . count($beaches) . "...\n";
    }
}

echo "\nDone! Generated sections for {$count} beaches" . ($errors ? " ({$errors} errors)" : "") . ".\n";

// Stats
if (!$dryRun) {
    $total = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_content_sections")['c'];
    $sectionCount = queryOne("SELECT COUNT(*) as c FROM beach_content_sections")['c'];
    echo "Total beaches with sections: {$total}\n";
    echo "Total sections: {$sectionCount}\n";
}
