<?php
/**
 * Single beach list view — public/shareable (/list?slug=...).
 *
 * Public lists are viewable by anyone; the owner gets manage controls
 * (add/remove beaches, share). Guests viewing a public list see a
 * "create your own" CTA — a registration loop.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

$currentLang = getCurrentLanguage();
$isEs = ($currentLang === 'es');
$viewer = isAuthenticated() ? currentUser() : null;
$viewerId = $viewer['id'] ?? null;

$L = $isEs ? [
    'by' => 'por',
    'public' => 'Pública',
    'private' => 'Privada',
    'beaches_one' => 'playa',
    'beaches_many' => 'playas',
    'share' => 'Compartir',
    'remove' => 'Quitar',
    'remove_confirm' => '¿Quitar esta playa de la lista?',
    'add_label' => 'Añadir una playa',
    'add_ph' => 'Busca una playa para añadir…',
    'empty_owner' => 'Esta lista está vacía. Busca una playa arriba para añadirla.',
    'empty_guest' => 'Esta lista aún no tiene playas.',
    'not_found_title' => 'Lista no encontrada',
    'not_found_body' => 'Esta lista no existe o es privada.',
    'cta_title' => '¿Quieres crear tu propia lista?',
    'cta_body' => 'Guarda tus playas favoritas y compártelas con amigos.',
    'cta_btn_guest' => 'Crear una cuenta gratis',
    'cta_btn_user' => 'Ver mis listas',
    'back' => 'Explorar playas',
] : [
    'by' => 'by',
    'public' => 'Public',
    'private' => 'Private',
    'beaches_one' => 'beach',
    'beaches_many' => 'beaches',
    'share' => 'Share',
    'remove' => 'Remove',
    'remove_confirm' => 'Remove this beach from the list?',
    'add_label' => 'Add a beach',
    'add_ph' => 'Search for a beach to add…',
    'empty_owner' => 'This list is empty. Search for a beach above to add it.',
    'empty_guest' => "This list doesn't have any beaches yet.",
    'not_found_title' => 'List not found',
    'not_found_body' => "This list doesn't exist or is private.",
    'cta_title' => 'Want to create your own list?',
    'cta_body' => 'Save your favorite beaches and share them with friends.',
    'cta_btn_guest' => 'Create a free account',
    'cta_btn_user' => 'View my lists',
    'back' => 'Explore beaches',
];

$slug = trim($_GET['slug'] ?? '');

$list = null;
if ($slug !== '') {
    $list = queryOne("
        SELECT l.*, u.name AS owner_name
        FROM beach_lists l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.slug = :slug AND (l.is_public = 1 OR l.user_id = :viewer)
    ", [':slug' => $slug, ':viewer' => $viewerId]);
}

if (!$list) {
    http_response_code(404);
    $pageTitle = $L['not_found_title'];
    $redesignLayout = useRedesign();
    $bodyClasses = trim(($bodyClasses ?? '') . ' rd-account');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-3xl mx-auto px-4 py-20 text-center">';
    echo '<div class="text-6xl mb-4">🔍</div>';
    echo '<h1 class="text-2xl font-bold text-gray-900 mb-2">' . h($L['not_found_title']) . '</h1>';
    echo '<p class="text-gray-600 mb-6">' . h($L['not_found_body']) . '</p>';
    echo '<a href="/" class="inline-block bg-sunset-400 hover:bg-sunset-500 text-ocean-900 font-semibold px-6 py-3 rounded-lg">' . h($L['back']) . '</a>';
    echo '</div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

$isOwner = $viewerId !== null && $list['user_id'] === $viewerId;
$isPublic = (int)$list['is_public'] === 1;

// Beaches in the list (ordered).
$beaches = query("
    SELECT b.*, li.notes AS list_notes, li.position
    FROM beach_list_items li
    INNER JOIN beaches b ON li.beach_id = b.id
    WHERE li.list_id = :list_id AND b.publish_status = 'published'
    ORDER BY li.position
", [':list_id' => $list['id']]) ?: [];

if (!empty($beaches)) {
    attachBeachMetadata($beaches);
}

$beachCount = count($beaches);
$listPath = '/list'; // single clean URL for both languages (localizes via lang cookie/session)
$shareUrl = getPublicBaseUrl() . $listPath . '?slug=' . rawurlencode($list['slug']);

$pageTitle = $list['name']; // header.php appends the site name
$pageDescription = $list['description'] ?: ($isEs
    ? 'Una lista de playas en Puerto Rico.'
    : 'A curated list of Puerto Rico beaches.');

// For the owner's "add a beach" autocomplete (published beaches not already in the list).
$addCandidates = [];
if ($isOwner) {
    $inList = array_column($beaches, 'id');
    $all = query("SELECT id, name, municipality FROM beaches WHERE publish_status = 'published' ORDER BY name") ?: [];
    foreach ($all as $b) {
        if (!in_array($b['id'], $inList, true)) {
            $addCandidates[] = ['id' => $b['id'], 'name' => $b['name'], 'municipality' => $b['municipality'] ?? ''];
        }
    }
}

$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => $isEs ? 'Mis listas' : 'My Lists', 'url' => '/lists'],
    ['name' => $list['name']],
];

$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-account');
include APP_ROOT . '/components/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
    </div>

    <div class="flex items-start justify-between gap-4 flex-wrap mb-8">
        <div class="min-w-0">
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-900"><?= h($list['name']) ?></h1>
                <?php if ($isOwner): ?>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $isPublic ? 'bg-palm-400/15 text-palm-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= h($isPublic ? $L['public'] : $L['private']) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($list['owner_name']) && !$isOwner): ?>
            <p class="text-gray-500 text-sm"><?= h($L['by']) ?> <?= h($list['owner_name']) ?></p>
            <?php endif; ?>
            <?php if (!empty($list['description'])): ?>
            <p class="text-gray-600 mt-2 max-w-2xl"><?= h($list['description']) ?></p>
            <?php endif; ?>
            <p class="text-gray-500 mt-2 text-sm"><?= $beachCount ?> <?= h($beachCount === 1 ? $L['beaches_one'] : $L['beaches_many']) ?></p>
        </div>
        <?php if ($isPublic): ?>
        <button type="button" data-action="shareList" data-action-args='[<?= json_encode($list['slug']) ?>]'
                class="inline-flex items-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm">
            <i data-lucide="share-2" class="w-4 h-4"></i><?= h($L['share']) ?>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($isOwner): ?>
    <!-- Owner: add a beach -->
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-8 relative">
        <label for="list-add-search" class="block text-sm font-medium text-gray-700 mb-2"><?= h($L['add_label']) ?></label>
        <div class="relative">
            <input type="text" id="list-add-search" autocomplete="off" placeholder="<?= h($L['add_ph']) ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sunset-400 focus:border-sunset-400">
            <div id="list-add-results" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($beaches)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-xl">
        <div class="text-6xl mb-4">🏝️</div>
        <p class="text-gray-600"><?= h($isOwner ? $L['empty_owner'] : $L['empty_guest']) ?></p>
    </div>
    <?php else: ?>
    <div id="beach-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($beaches as $beach): ?>
        <div class="relative">
            <?php
                $distance = null;
                $isFavorite = false;
                include APP_ROOT . '/components/beach-card.php';
            ?>
            <?php if ($isOwner): ?>
            <button type="button"
                    class="absolute top-3 left-3 z-10 inline-flex items-center gap-1 bg-white/90 hover:bg-white text-red-600 text-xs font-medium px-2.5 py-1 rounded-full shadow"
                    data-action="removeBeachFromList" data-action-args='[<?= json_encode($beach['id']) ?>]'
                    data-action-confirm="<?= h($L['remove_confirm']) ?>">
                <i data-lucide="x" class="w-3.5 h-3.5"></i><?= h($L['remove']) ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$isOwner): ?>
    <!-- Registration / engagement loop: viewers of a public list are nudged to make their own -->
    <div class="mt-12 bg-ocean-900 text-white rounded-2xl p-8 text-center">
        <h2 class="text-2xl font-bold mb-2"><?= h($L['cta_title']) ?></h2>
        <p class="text-white/70 mb-6 max-w-md mx-auto"><?= h($L['cta_body']) ?></p>
        <?php if ($viewerId): ?>
        <a href="/lists" class="inline-block bg-sunset-400 hover:bg-sunset-500 text-ocean-900 font-semibold px-6 py-3 rounded-lg">
            <?= h($L['cta_btn_user']) ?>
        </a>
        <?php else: ?>
        <a href="<?= h(routeUrl('login', $currentLang)) ?>?redirect=<?= rawurlencode('/lists') ?>"
           class="inline-block bg-sunset-400 hover:bg-sunset-500 text-ocean-900 font-semibold px-6 py-3 rounded-lg">
            <?= h($L['cta_btn_guest']) ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Beach Details Drawer (beach cards open it) -->
<div id="beach-drawer" class="drawer-overlay" data-action="closeBeachDrawer" data-action-args='["__event__"]'>
    <div class="drawer-content" data-action-stop data-action="noop" data-on="click">
        <div id="drawer-content-inner"></div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
window.BeachFinder = window.BeachFinder || {};
window.BeachFinder.beaches = <?= json_encode($beaches ?: []) ?>;
window.BeachFinder.csrfToken = <?= json_encode(csrfToken()) ?>;
window.BeachFinder.isAuthenticated = <?= $viewerId ? 'true' : 'false' ?>;
window.BF_LISTS = {
    currentListId: <?= (int)$list['id'] ?>,
    isOwner: <?= $isOwner ? 'true' : 'false' ?>,
    listBasePath: <?= json_encode($listPath) ?>,
    origin: <?= json_encode(getPublicBaseUrl()) ?>,
    addCandidates: <?= json_encode($addCandidates) ?>,
    strings: {
        add: <?= json_encode($isEs ? 'Añadir' : 'Add') ?>,
        noMatches: <?= json_encode($isEs ? 'Sin resultados' : 'No matches') ?>,
        linkCopied: <?= json_encode($isEs ? 'Enlace copiado' : 'Link copied!') ?>,
        genericError: <?= json_encode($isEs ? 'Algo salió mal. Inténtalo de nuevo.' : 'Something went wrong. Please try again.') ?>
    }
};
</script>
<script defer src="/assets/js/lists.js?v=1.0" <?= cspNonceAttr() ?>></script>

<?php include APP_ROOT . '/components/footer.php'; ?>
