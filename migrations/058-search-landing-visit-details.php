#!/usr/bin/env php
<?php
/** September search review: remove unverified hours and swimming guarantees.
 * Location references: discoverpuertorico.com/regions/north/dorado and
 * discoverpuertorico.com/es/regiones/luquillo. Neither verifies live operating
 * hours or today's services. Preserve coordinates, amenities, photos and reviews.
 */
require_once __DIR__ . '/../inc/db.php';
$entries = [
    'balneario-de-dorado' => [
        'description' => 'Plan a beach visit on the north coast in Dorado. Compare the mapped entrance, parking details and listed facilities below. Confirm which services are operating before making a special trip, and check current local beach conditions before entering the water.',
        'description_es' => 'Planifica una visita a la playa en la costa norte de Dorado. Compara la entrada en el mapa, el estacionamiento y las facilidades registradas más abajo. Confirma qué servicios están disponibles antes de hacer un viaje especial y revisa las condiciones locales antes de entrar al agua.',
    ],
    'playa-de-luquillo' => [
        'description' => 'Explore the Luquillo shoreline and plan your visit around a specific beach entrance. Check the map, parking notes and listed facilities below. Access to managed facilities may have different hours from the surrounding shore; confirm current services before you travel.',
        'description_es' => 'Explora la costa de Luquillo y planifica tu visita a partir de una entrada específica. Consulta el mapa, las notas de estacionamiento y las facilidades más abajo. Las instalaciones de un balneario pueden tener un horario distinto al de la costa alrededor; confirma los servicios antes de viajar.',
    ],
];
foreach ($entries as $slug => $fields) {
    $fields += [
        'best_time' => 'Check the forecast and current beach advisories for your visit date. Confirm opening hours and parking access directly with the facility; this listing does not provide a verified live schedule.',
        'best_time_es' => 'Consulta el pronóstico y los avisos de playa para el día de tu visita. Confirma el horario y el acceso al estacionamiento directamente con las instalaciones; esta ficha no ofrece un horario en vivo verificado.',
        'safety_info' => 'Conditions can change even where the water looks calm. Follow posted warnings and local lifeguard instructions. Confirm whether lifeguards are on duty; a family-friendly listing does not guarantee safe swimming.',
        'safety_info_es' => 'Las condiciones pueden cambiar aunque el agua parezca tranquila. Sigue los avisos y las instrucciones de los salvavidas. Confirma si hay salvavidas de servicio; una ficha de playa familiar no garantiza un baño seguro.',
    ];
    $assignments = array_map(fn($key) => "$key = :$key", array_keys($fields));
    $params = [':slug' => $slug];
    foreach ($fields as $key => $value) $params[":$key"] = $value;
    if (!execute('UPDATE beaches SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE slug = :slug', $params)) {
        throw new RuntimeException('Could not update visit details for ' . $slug);
    }
}
echo "Updated Dorado and Luquillo visit details.\n";
