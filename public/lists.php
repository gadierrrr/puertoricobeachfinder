<?php
/**
 * My Lists page — user-managed custom beach collections.
 *
 * Exposes the previously-hidden beach_lists feature (backend: public/api/lists.php).
 * Public lists are shareable via /list?slug=... which doubles as a registration loop
 * (guests viewing a shared list get a "create your own" CTA).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

requireAuth();

$user = currentUser();
$currentLang = getCurrentLanguage();
$isEs = ($currentLang === 'es');

$L = $isEs ? [
    'title' => 'Mis listas',
    'subtitle_one' => 'lista',
    'subtitle_many' => 'listas',
    'create' => 'Crear lista',
    'empty_title' => 'Aún no tienes listas',
    'empty_body' => 'Crea listas como "Viaje a Rincón" o "Playas para snorkel" y compártelas con amigos.',
    'empty_cta' => 'Crear tu primera lista',
    'view' => 'Ver',
    'edit' => 'Editar',
    'delete' => 'Eliminar',
    'share' => 'Compartir',
    'public' => 'Pública',
    'private' => 'Privada',
    'beaches_one' => 'playa',
    'beaches_many' => 'playas',
    'name_label' => 'Nombre de la lista',
    'name_ph' => 'p. ej. Viaje a Rincón',
    'desc_label' => 'Descripción (opcional)',
    'desc_ph' => '¿De qué trata esta lista?',
    'public_label' => 'Hacer pública (cualquiera con el enlace puede verla)',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'new_list' => 'Nueva lista',
    'edit_list' => 'Editar lista',
    'delete_confirm' => '¿Eliminar esta lista? Esta acción no se puede deshacer.',
] : [
    'title' => 'My Lists',
    'subtitle_one' => 'list',
    'subtitle_many' => 'lists',
    'create' => 'Create list',
    'empty_title' => "You don't have any lists yet",
    'empty_body' => 'Create lists like "Rincón trip" or "Snorkeling spots" and share them with friends.',
    'empty_cta' => 'Create your first list',
    'view' => 'View',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'share' => 'Share',
    'public' => 'Public',
    'private' => 'Private',
    'beaches_one' => 'beach',
    'beaches_many' => 'beaches',
    'name_label' => 'List name',
    'name_ph' => 'e.g. Rincón trip',
    'desc_label' => 'Description (optional)',
    'desc_ph' => "What's this list about?",
    'public_label' => 'Make public (anyone with the link can view)',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'new_list' => 'New list',
    'edit_list' => 'Edit list',
    'delete_confirm' => 'Delete this list? This cannot be undone.',
];

$pageTitle = $L['title']; // header.php appends the site name
$pageDescription = $isEs
    ? 'Crea y comparte tus propias listas de playas en Puerto Rico.'
    : 'Create and share your own custom beach lists for Puerto Rico.';

// User's lists with beach counts (mirrors api/lists.php getUserLists).
$lists = query("
    SELECT l.*, COUNT(li.id) AS beach_count
    FROM beach_lists l
    LEFT JOIN beach_list_items li ON l.id = li.list_id
    WHERE l.user_id = :user_id
    GROUP BY l.id
    ORDER BY l.updated_at DESC
", [':user_id' => $user['id']]) ?: [];

// Single clean URL for both languages — the page localizes via the lang cookie/session,
// so no /es route or nginx rule is needed.
$listPath = '/list';

$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => __('profile.my_profile'), 'url' => '/profile'],
    ['name' => $L['title']],
];

include APP_ROOT . '/components/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
    </div>

    <div class="flex items-center justify-between mb-8 gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?= h($L['title']) ?></h1>
            <p class="text-gray-600 mt-1">
                <?= count($lists) ?> <?= h(count($lists) === 1 ? $L['subtitle_one'] : $L['subtitle_many']) ?>
            </p>
        </div>
        <button type="button" data-action="openCreateListModal"
                class="inline-flex items-center gap-2 bg-sunset-400 hover:bg-sunset-500 text-ocean-900 font-semibold px-5 py-3 rounded-lg transition-colors">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span><?= h($L['create']) ?></span>
        </button>
    </div>

    <?php if (empty($lists)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-xl">
        <div class="text-6xl mb-4">📋</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2"><?= h($L['empty_title']) ?></h2>
        <p class="text-gray-500 mb-6 max-w-md mx-auto"><?= h($L['empty_body']) ?></p>
        <button type="button" data-action="openCreateListModal"
                class="inline-flex items-center gap-2 bg-sunset-400 hover:bg-sunset-500 text-ocean-900 px-6 py-3 rounded-lg font-medium">
            <i data-lucide="plus" class="w-5 h-5"></i><?= h($L['empty_cta']) ?>
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($lists as $list): ?>
        <?php
            $listUrl = $listPath . '?slug=' . rawurlencode($list['slug']);
            $count = (int)$list['beach_count'];
            $isPublic = (int)$list['is_public'] === 1;
        ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col">
            <a href="<?= h($listUrl) ?>" class="block p-5 flex-1">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="text-lg font-semibold text-gray-900 leading-snug"><?= h($list['name']) ?></h3>
                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full <?= $isPublic ? 'bg-palm-400/15 text-palm-700' : 'bg-gray-100 text-gray-500' ?>">
                        <?= h($isPublic ? $L['public'] : $L['private']) ?>
                    </span>
                </div>
                <?php if (!empty($list['description'])): ?>
                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= h($list['description']) ?></p>
                <?php endif; ?>
                <p class="text-sm text-gray-500">
                    <?= $count ?> <?= h($count === 1 ? $L['beaches_one'] : $L['beaches_many']) ?>
                </p>
            </a>
            <div class="border-t border-gray-100 px-5 py-3 flex items-center gap-4 text-sm">
                <a href="<?= h($listUrl) ?>" class="text-blue-600 hover:text-blue-700 font-medium"><?= h($L['view']) ?></a>
                <button type="button" class="text-gray-600 hover:text-gray-900"
                        data-action="openEditListModal"
                        data-action-args='[<?= (int)$list['id'] ?>, <?= json_encode($list['name']) ?>, <?= json_encode($list['description'] ?? '') ?>, <?= $isPublic ? 'true' : 'false' ?>]'>
                    <?= h($L['edit']) ?>
                </button>
                <?php if ($isPublic): ?>
                <button type="button" class="text-gray-600 hover:text-gray-900"
                        data-action="shareList" data-action-args='[<?= json_encode($list['slug']) ?>]'>
                    <?= h($L['share']) ?>
                </button>
                <?php endif; ?>
                <button type="button" class="text-red-500 hover:text-red-600 ml-auto"
                        data-action="deleteList" data-action-args='[<?= (int)$list['id'] ?>]'
                        data-action-confirm="<?= h($L['delete_confirm']) ?>">
                    <?= h($L['delete']) ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Create / Edit list modal -->
<div id="list-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="list-modal-title" data-action="closeListModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" data-action-stop data-action="noop" data-on="click">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id="list-modal-title" class="text-lg font-semibold text-gray-900"><?= h($L['new_list']) ?></h2>
            <button type="button" data-action="closeListModal" class="text-gray-400 hover:text-gray-600" aria-label="<?= h($L['cancel']) ?>">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="list-form" class="p-6 space-y-4" data-action="submitListForm" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="list_id" id="list-form-id" value="">
            <div>
                <label for="list-form-name" class="block text-sm font-medium text-gray-700 mb-1"><?= h($L['name_label']) ?></label>
                <input type="text" name="name" id="list-form-name" maxlength="100" required
                       placeholder="<?= h($L['name_ph']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sunset-400 focus:border-sunset-400">
            </div>
            <div>
                <label for="list-form-desc" class="block text-sm font-medium text-gray-700 mb-1"><?= h($L['desc_label']) ?></label>
                <textarea name="description" id="list-form-desc" rows="2"
                          placeholder="<?= h($L['desc_ph']) ?>"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sunset-400 focus:border-sunset-400"></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_public" id="list-form-public" value="1" class="rounded border-gray-300 text-sunset-500 focus:ring-sunset-400">
                <?= h($L['public_label']) ?>
            </label>
            <div id="list-form-error" class="hidden text-sm text-red-600"></div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" data-action="closeListModal" class="px-4 py-2 text-gray-600 hover:text-gray-900"><?= h($L['cancel']) ?></button>
                <button type="submit" id="list-form-submit" class="bg-sunset-400 hover:bg-sunset-500 text-ocean-900 font-semibold px-5 py-2 rounded-lg transition-colors"><?= h($L['save']) ?></button>
            </div>
        </form>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
window.BeachFinder = window.BeachFinder || {};
window.BeachFinder.csrfToken = <?= json_encode(csrfToken()) ?>;
window.BF_LISTS = {
    listBasePath: <?= json_encode($listPath) ?>,
    origin: <?= json_encode(getPublicBaseUrl()) ?>,
    strings: {
        newList: <?= json_encode($L['new_list']) ?>,
        editList: <?= json_encode($L['edit_list']) ?>,
        linkCopied: <?= json_encode($isEs ? 'Enlace copiado' : 'Link copied!') ?>,
        genericError: <?= json_encode($isEs ? 'Algo salió mal. Inténtalo de nuevo.' : 'Something went wrong. Please try again.') ?>
    }
};
</script>
<script defer src="/assets/js/lists.js?v=1.0" <?= cspNonceAttr() ?>></script>

<?php include APP_ROOT . '/components/footer.php'; ?>
