<?php
/**
 * Beaches Near San Juan Airport - SEO Landing Page
 * Target keywords: beaches near san juan airport, sju layover beach, airport beach san juan
 * Monthly searches: 1,300
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/components/seo-schemas.php';

require_once APP_ROOT . '/inc/search_landings.php';
$lang = getCurrentLanguage();
$isEs = $lang === 'es';
$copy = searchLandingCopy('/beaches-near-san-juan-airport', $lang);
$pageTitle = $copy['title'];
$pageDescription = $copy['description'];
$canonicalUrl = getPublicBaseUrl() . localizePath('/beaches-near-san-juan-airport', $lang);
$collectionKey = 'beaches-near-san-juan-airport';
$collectionAnchorId = 'beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$airportBeaches = $collectionData['beaches'];
$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}
$pageFaqs = $isEs ? [
    ['question'=>'¿Puedo visitar una playa durante una escala en SJU?', 'answer'=>'Depende del tiempo disponible después de los trámites del aeropuerto, el tránsito y los requisitos de tu aerolínea. No hay una duración de escala que garantice una visita. Consulta la hora límite de embarque y organiza el regreso antes de salir.'],
    ['question'=>'¿Qué zona de playa puedo comparar primero?', 'answer'=>'Empieza por Isla Verde y Carolina en el mapa. Escoge una entrada concreta y abre cómo llegar para consultar la ruta actual. La distancia mostrada en esta lista es geográfica, no un tiempo de viaje.'],
    ['question'=>'¿Dónde puedo dejar el equipaje?', 'answer'=>'Confirma directamente con tu alojamiento o proveedor si ofrece almacenamiento, el horario y el costo. No presupongas que un hotel acepta equipaje de personas que no se hospedan allí.'],
    ['question'=>'¿Están garantizados los baños, las duchas y el transporte?', 'answer'=>'Consulta las facilidades registradas en la ficha de cada playa y confirma su disponibilidad actual. Acuerda el punto de recogido y revisa la tarifa con tu proveedor de transporte. Los servicios y las condiciones pueden cambiar.'],
] : [
    ['question'=>'Can I visit a beach during a layover at SJU?', 'answer'=>'It depends on the time left after airport procedures, traffic and your airline’s requirements. No single layover length guarantees a beach visit. Check your boarding deadline and arrange your return before leaving.'],
    ['question'=>'Which beach area should I compare first?', 'answer'=>'Start with Isla Verde and Carolina on the map. Choose a specific beach entrance and open directions for the current route. Distances in this list are geographic estimates, not driving times.'],
    ['question'=>'Where can I leave my luggage?', 'answer'=>'Confirm storage availability, opening hours and prices directly with your accommodation or storage provider. Do not assume a hotel accepts bags from non-guests.'],
    ['question'=>'Are bathrooms, showers and transport guaranteed?', 'answer'=>'Review the listed facilities on each beach page and confirm current availability. Agree on a pickup point and check the fare with your transport provider. Services and conditions can change.'],
];
$extraHead = collectionPageSchema($pageTitle, $pageDescription, $airportBeaches) . faqSchema($pageFaqs) . websiteSchema();
$bodyVariant = 'collection-dark';
$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';
include APP_ROOT . '/components/collection/explorer.php';
?>
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-6"><?= h($isEs ? 'Antes de salir del aeropuerto' : 'Before leaving the airport') ?></h2>
        <p class="mb-6"><?= h($isEs ? 'Consulta los vuelos y servicios de SJU y los requisitos de tu aerolínea. Planifica tiempo para regresar, recoger equipaje si corresponde y pasar los controles necesarios.' : 'Check SJU flight information and services, plus your airline’s requirements. Allow time to return, retrieve any stored luggage and complete the necessary airport checks.') ?></p>
        <p><a class="underline" href="https://aeropuertosju.com/vuelos/" rel="noopener"><?= h($isEs ? 'Información oficial de vuelos de SJU' : 'Official SJU flight information') ?></a> · <a class="underline" href="https://aeropuertosju.com/servicios/" rel="noopener"><?= h($isEs ? 'Servicios del aeropuerto' : 'Airport services') ?></a></p>
    </div>
</section>
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-6"><?= h($isEs ? 'Preguntas sobre playas cerca de SJU' : 'Beaches near SJU: common questions') ?></h2>
        <div class="space-y-4">
        <?php foreach ($pageFaqs as $faq): ?>
            <details class="border border-warm-200 rounded-lg p-6">
                <summary class="cursor-pointer font-semibold"><?= h($faq['question']) ?></summary>
                <p class="mt-4"><?= h($faq['answer']) ?></p>
            </details>
        <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
require_once APP_ROOT . '/inc/tours.php';
$toursHtml = renderGuideToursSection('beaches-near-san-juan-airport', $lang, [
    'sub' => $isEs ? 'Compara experiencias para un día con tiempo suficiente. Confirma la duración y el transporte antes de reservar en Viator.' : 'Compare experiences for a day with enough time. Confirm duration and transport before booking on Viator.',
    'section_class' => '',
]);
if ($toursHtml !== ''): ?>
<section class="py-12"><div class="max-w-4xl mx-auto px-4"><?= $toursHtml ?></div></section>
<?php endif;
$skipAppScripts = true;
$extraScripts = '<script defer src="/assets/js/collection-explorer.min.js?v=2.0" ' . cspNonceAttr() . '></script>';
include APP_ROOT . '/components/footer.php';
