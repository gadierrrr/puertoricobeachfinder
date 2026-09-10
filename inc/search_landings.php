<?php
/** Focused landing-page copy from the September 2026 search-query review.
 * Keep practical details in the existing beach records; no invented hours,
 * guaranteed swimming conditions, prices, or driving times in search snippets.
 */
function searchLandingCopy(?string $path = null, ?string $lang = null): ?array
{
    $lang = $lang ?? getCurrentLanguage();
    $path = $path ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $path = localizePath($path, 'en');
    $copy = [
        '/beaches-near-san-juan' => [
            'en' => ['San Juan Beaches: Compare Locations, Parking & Maps', 'Compare beaches around San Juan, Puerto Rico. Explore the map, check listed amenities and open directions to the beach that fits your plans.', 'Choosing a beach around San Juan', 'Use this list for a trip based in San Juan. Check the map for the actual beach entrance and compare the facilities on each beach page. Distances are geographic estimates, not driving times.'],
            'es' => ['Playas cerca de San Juan: mapa, acceso y estacionamiento', 'Compara playas cerca de San Juan, Puerto Rico. Consulta el mapa, las facilidades registradas y cómo llegar a cada entrada antes de salir.', 'Escoge una playa cerca de San Juan', 'Esta lista parte de San Juan. Busca la entrada de cada playa en el mapa y compara las facilidades en su ficha. Las distancias son estimados geográficos, no tiempos de viaje.'],
        ],
        '/beaches-near-san-juan-airport' => [
            'en' => ['Beaches Near San Juan Airport (SJU): Map & Visit Planning', 'Compare beaches near SJU airport, including the Isla Verde area. Find beach directions and plan transport, luggage and your return before leaving the airport.', 'A beach visit before or after your flight', 'Start with the Isla Verde and Carolina area on the map. Before leaving SJU, confirm your airline’s check-in requirements, luggage arrangements and a return ride. A short distance alone does not make a layover long enough for a beach visit.'],
            'es' => ['Playas cerca del aeropuerto de San Juan (SJU): mapa y acceso', 'Compara playas cerca del aeropuerto SJU, incluyendo Isla Verde. Consulta cómo llegar y organiza transporte, equipaje y regreso antes de salir del aeropuerto.', 'Una visita a la playa antes o después de tu vuelo', 'Empieza por el área de Isla Verde y Carolina en el mapa. Antes de salir de SJU, confirma los requisitos de tu aerolínea, qué harás con el equipaje y el transporte de regreso. Una distancia corta no garantiza que tu escala sea suficiente.'],
        ],
        '/hidden-beaches-puerto-rico' => [
            'en' => ['Hidden Beaches in Puerto Rico: Access, Maps & Facilities', 'Find less-visited beaches in Puerto Rico. Compare access routes, listed amenities and locations before choosing a secluded beach for your trip.', 'Plan the access, not just the photo', 'Open a beach’s details before choosing a secluded spot. Check whether access is by road, trail or boat, and confirm the facilities you need. Save a second option nearby in case access or conditions change.'],
            'es' => ['Playas escondidas de Puerto Rico: acceso, mapas y facilidades', 'Explora playas menos concurridas de Puerto Rico. Compara rutas de acceso, mapas y facilidades registradas para planificar tu visita.', 'Planifica el acceso, además de la foto', 'Abre la ficha antes de escoger una playa apartada. Revisa si se llega por carretera, sendero o bote y confirma las facilidades que necesitas. Guarda una segunda opción cercana por si cambia el acceso o las condiciones.'],
        ],
        '/beaches-in-salinas' => [
            'en' => ['Beaches in Salinas, Puerto Rico: Map, Access & Directions', 'Explore beaches in Salinas with photos, maps and listed amenities. Compare access points and open directions to plan a visit to Puerto Rico’s south coast.', 'Choose your Salinas beach access', 'Compare the access notes for each beach instead of navigating only to the town center. For an offshore stop, arrange the boat trip and return with the operator before setting out.'],
            'es' => ['Playas en Salinas, Puerto Rico: mapa, acceso y cómo llegar', 'Encuentra playas en Salinas con fotos, mapa y facilidades registradas. Compara los accesos y consulta cómo llegar para planificar tu visita a la costa sur.', 'Escoge el acceso a tu playa en Salinas', 'Compara las notas de acceso de cada playa en vez de dirigirte solamente al centro del pueblo. Si vas a un cayo, acuerda el viaje y el regreso con el operador antes de salir.'],
        ],
        '/beaches-in-mayaguez' => [
            'en' => ['Beaches in Mayagüez, Puerto Rico: Photos, Access & Map', 'Compare beaches in Mayagüez with photos, locations and access details. Check listed facilities, save your favorites and open beach directions.', 'Find a specific beach in Mayagüez', 'Use the individual beach pin to plan your route. Compare the access notes and facilities, then keep nearby west-coast beaches as alternatives. A high review score is not a report of today’s water conditions.'],
            'es' => ['Playas en Mayagüez, Puerto Rico: fotos, acceso y mapa', 'Compara playas en Mayagüez con fotos, ubicaciones y detalles de acceso. Consulta facilidades, guarda tus favoritas y abre las indicaciones para llegar.', 'Encuentra una playa específica en Mayagüez', 'Usa la ubicación de la playa para planificar la ruta. Compara el acceso y las facilidades y guarda alternativas cercanas en la costa oeste. Una buena puntuación no informa las condiciones del agua de hoy.'],
        ],
        '/beach/balneario-de-dorado' => [
            'en' => ['Balneario de Dorado: Parking, Facilities & Directions', 'Plan a visit to Balneario de Dorado in Puerto Rico. Find photos, listed facilities, parking information and directions to the beach entrance.', 'Before visiting Balneario de Dorado', 'Use the beach entrance shown in directions, then check the parking and facilities sections below. Confirm current opening hours, fees and services before making a special trip; availability can change.'],
            'es' => ['Balneario de Dorado: estacionamiento, facilidades y acceso', 'Planifica tu visita al Balneario de Dorado. Consulta fotos, estacionamiento, facilidades registradas y cómo llegar a la entrada de la playa.', 'Antes de visitar el Balneario de Dorado', 'Usa la entrada indicada en cómo llegar y consulta el estacionamiento y las facilidades más abajo. Confirma el horario, las tarifas y los servicios antes de hacer un viaje especial; pueden cambiar.'],
        ],
        '/beach/playa-de-luquillo' => [
            'en' => ['Luquillo Beach, Puerto Rico: Parking, Access & Visit Guide', 'Planning a visit to Luquillo Beach? See photos, parking and access details, beach directions, and what to confirm about opening hours before you go.', 'Luquillo Beach hours and access', 'Check which access point you plan to use: opening hours and services at a managed beach facility may differ from the surrounding shoreline. This page does not provide a verified live opening schedule. Confirm hours and parking charges before you travel.'],
            'es' => ['Playa de Luquillo: estacionamiento, acceso y visita', 'Planifica tu visita a la playa de Luquillo con fotos, estacionamiento, cómo llegar y lo que debes confirmar sobre horarios antes de salir.', 'Horario y acceso a la playa de Luquillo', 'Identifica la entrada que vas a usar: el horario y los servicios de un balneario pueden ser distintos a los de la costa alrededor. Esta página no ofrece un horario en vivo verificado. Confirma el horario y el costo del estacionamiento antes de viajar.'],
        ],
        '/guides/beach-safety-tips' => [
            'en' => ['Puerto Rico Beach Safety: Rip Currents, Flags & Jellyfish', 'Plan for beach hazards in Puerto Rico: rip currents, warning flags, jellyfish and changing conditions. Check current local advisories before entering the water.'],
            'es' => ['Seguridad en playas de Puerto Rico: corrientes y avisos', 'Planifica tu visita a las playas de Puerto Rico: corrientes, banderas, aguavivas y condiciones cambiantes. Revisa los avisos locales antes de entrar al agua.'],
        ],
        '/guides/best-time-visit-puerto-rico-beaches' => [
            'en' => ['When to Visit Puerto Rico Beaches: Weather & Seasonal Guide', 'Compare seasons for a Puerto Rico beach trip. Plan around weather, crowds and changing ocean conditions, and check the forecast before your beach day.'],
            'es' => ['Cuándo visitar las playas de Puerto Rico: clima y temporadas', 'Compara las temporadas para visitar playas de Puerto Rico. Planifica según el clima, la afluencia y las condiciones del mar; revisa el pronóstico antes de salir.'],
        ],
    ];
    if (!isset($copy[$path][$lang])) return null;
    $entry = $copy[$path][$lang];
    return ['title'=>$entry[0], 'description'=>$entry[1], 'heading'=>$entry[2] ?? '', 'intro'=>$entry[3] ?? '', 'path'=>$path];
}
