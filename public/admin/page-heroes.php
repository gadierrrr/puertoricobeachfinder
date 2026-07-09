<?php
/**
 * Admin — Page header and hero image manager.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/page_heroes.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice = ['type' => 'success', 'message' => 'Header image published.'];
    try {
        if (!validateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Your session expired. Refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? 'save');
        $scope = (string) ($_POST['scope'] ?? '');
        $key = trim((string) ($_POST['key'] ?? ''));

        if ($action === 'remove') {
            pageHeroDeleteEntry($scope, $key);
            $notice['message'] = 'Header image override removed.';
        } elseif ($action === 'save') {
            $settings = getPageHeroSettings();
            $storedKey = $scope === 'page' ? pageHeroNormalizePath($key) : $key;
            $current = $settings[$scope === 'page' ? 'pages' : 'families'][$storedKey] ?? null;
            $image = is_array($current) ? (string) ($current['image'] ?? '') : '';

            if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = savePageHeroUpload($_FILES['photo']);
            }
            if ($image === '') {
                throw new RuntimeException('Choose a photo before publishing.');
            }

            pageHeroSetEntry($scope, $key, [
                'image' => $image,
                'position' => (string) ($_POST['position'] ?? 'center center'),
                'overlay' => (int) ($_POST['overlay'] ?? 46),
            ]);
        } else {
            throw new RuntimeException('Unknown action.');
        }
    } catch (Throwable $e) {
        $notice = ['type' => 'error', 'message' => $e->getMessage()];
    }

    $_SESSION['page_hero_notice'] = $notice;
    header('Location: /admin/page-heroes');
    exit;
}

$pageTitle = 'Page Headers & Heroes';
$pageSubtitle = 'Publish responsive header photography across every public page family';
include __DIR__ . '/components/header.php';

$families = pageHeroFamilies();
$settings = getPageHeroSettings();
$notice = $_SESSION['page_hero_notice'] ?? null;
unset($_SESSION['page_hero_notice']);
$positions = [
    'center center' => 'Center',
    'center top' => 'Top',
    'center bottom' => 'Bottom',
    'left center' => 'Left',
    'right center' => 'Right',
];

function pageHeroAdminCard(string $scope, string $key, string $label, string $description, string $example, ?array $entry, array $positions): void
{
    $hasImage = is_array($entry) && !empty($entry['image']);
    $position = (string) ($entry['position'] ?? 'center center');
    $overlay = (int) ($entry['overlay'] ?? 46);
    ?>
    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="relative aspect-[16/7] overflow-hidden bg-slate-900">
            <?php if ($hasImage): ?>
            <img src="<?= h($entry['image']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover" style="object-position:<?= h($position) ?>">
            <div class="absolute inset-0 bg-slate-950" style="opacity:<?= $overlay / 100 ?>"></div>
            <?php else: ?>
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#0f2d3a,#165f73)]"></div>
            <div class="absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 18% 28%,#facc15 0 2px,transparent 3px),radial-gradient(circle at 78% 62%,#38bdf8 0 2px,transparent 3px);background-size:36px 36px"></div>
            <?php endif; ?>
            <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                <p class="text-[10px] font-bold uppercase tracking-[.22em] text-yellow-300"><?= h($scope === 'family' ? 'Page family' : 'Specific page') ?></p>
                <h2 class="mt-1 text-xl font-black leading-tight"><?= h($label) ?></h2>
            </div>
            <span class="absolute right-3 top-3 rounded-full border border-white/25 bg-black/30 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur">
                <?= $hasImage ? 'Published' : 'Uses page default' ?>
            </span>
        </div>

        <form method="post" enctype="multipart/form-data" class="space-y-4 p-5">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="scope" value="<?= h($scope) ?>">
            <input type="hidden" name="key" value="<?= h($key) ?>">

            <div>
                <p class="text-sm leading-6 text-slate-600"><?= h($description) ?></p>
                <a class="mt-1 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-800" href="<?= h($example) ?>" target="_blank" rel="noopener">Preview <?= h($example) ?> ↗</a>
            </div>

            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500"><?= $hasImage ? 'Replace photo' : 'Add photo' ?></span>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                       class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:font-semibold file:text-white">
                <span class="mt-1 block text-[11px] text-slate-400">JPEG, PNG or WebP · at least 800 × 300 · max 15 MB</span>
            </label>

            <div class="grid grid-cols-[1fr,1.15fr] gap-3">
                <label class="block text-xs font-semibold text-slate-600">Focal point
                    <select name="position" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <?php foreach ($positions as $value => $positionLabel): ?>
                        <option value="<?= h($value) ?>"<?= $position === $value ? ' selected' : '' ?>><?= h($positionLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-600">Text contrast · <?= $overlay ?>%
                    <input type="range" name="overlay" min="0" max="80" step="2" value="<?= $overlay ?>" class="mt-3 block w-full accent-slate-900">
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                    <?= $hasImage ? 'Save changes' : 'Upload & publish' ?>
                </button>
                <?php if ($hasImage): ?>
                <button type="submit" name="action" value="remove" class="rounded-lg border border-red-200 px-3 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Remove</button>
                <?php endif; ?>
            </div>
        </form>
    </article>
    <?php
}
?>

<?php if (is_array($notice)): ?>
<div class="mb-6 rounded-xl border px-4 py-3 text-sm font-semibold <?= $notice['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>" role="status">
    <?= h((string) $notice['message']) ?>
</div>
<?php endif; ?>

<section class="mb-8 overflow-hidden rounded-2xl bg-slate-950 text-white shadow-lg">
    <div class="grid gap-6 p-6 md:grid-cols-[1.3fr,.7fr] md:p-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.24em] text-yellow-300">One visual control room</p>
            <h2 class="mt-2 max-w-2xl text-3xl font-black tracking-tight">Give every page a sense of place.</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Family images provide complete coverage. Add a URL override when one page needs its own photograph. Uploaded files are resized, converted to WebP and published immediately.</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
            <p class="font-bold text-white">Beach profiles stay independent</p>
            <p class="mt-1 leading-5">Routes such as <code class="text-yellow-300">/beach/flamenco-beach</code> and <code class="text-yellow-300">/es/playa/…</code> are blocked here. Their hero images continue to come from Beach Management.</p>
        </div>
    </div>
</section>

<div class="mb-5 flex items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-[.2em] text-blue-600">Default coverage</p>
        <h2 class="mt-1 text-2xl font-black text-slate-900">Page families</h2>
    </div>
    <span class="text-sm text-slate-500"><?= count($families) ?> families cover the public site</span>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <?php foreach ($families as $key => $family): ?>
        <?php pageHeroAdminCard('family', $key, $family['label'], $family['description'], $family['example'], $settings['families'][$key] ?? null, $positions); ?>
    <?php endforeach; ?>
</div>

<section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 lg:grid-cols-[.75fr,1.25fr]">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-600">Fine control</p>
            <h2 class="mt-1 text-2xl font-black text-slate-900">Add a page override</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Enter the clean public URL, including <code>/es</code> for a Spanish-only override. Query strings are not needed.</p>
        </div>
        <form method="post" enctype="multipart/form-data" class="grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="scope" value="page">
            <label class="sm:col-span-2 text-xs font-bold uppercase tracking-wider text-slate-500">Public URL path
                <input type="text" name="key" required placeholder="/guides/snorkeling-guide" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm normal-case tracking-normal text-slate-900">
            </label>
            <label class="sm:col-span-2 text-xs font-bold uppercase tracking-wider text-slate-500">Header photo
                <input type="file" name="photo" required accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white p-2 text-xs file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:font-semibold file:text-white">
            </label>
            <label class="text-xs font-semibold text-slate-600">Focal point
                <select name="position" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <?php foreach ($positions as $value => $positionLabel): ?><option value="<?= h($value) ?>"><?= h($positionLabel) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs font-semibold text-slate-600">Text contrast · 46%
                <input type="range" name="overlay" min="0" max="80" step="2" value="46" class="mt-3 block w-full accent-slate-900">
            </label>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Add page override</button>
        </form>
    </div>
</section>

<?php if (!empty($settings['pages'])): ?>
<div class="mb-5 mt-10">
    <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-600">Published exceptions</p>
    <h2 class="mt-1 text-2xl font-black text-slate-900">Page-specific overrides</h2>
</div>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <?php foreach ($settings['pages'] as $path => $entry): ?>
        <?php pageHeroAdminCard('page', $path, $path, 'Overrides the family image on this exact public URL.', $path, $entry, $positions); ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>

