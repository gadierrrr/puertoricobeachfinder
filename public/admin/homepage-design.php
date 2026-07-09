<?php
/**
 * Admin — Homepage Design editor.
 *
 * Live editor for the redesign homepage: display font picker, hero background
 * (colour presets / custom / uploaded photos with opacity+darken), paper
 * texture, and draggable hero stickers. The preview iframe loads the real
 * homepage with ?rdedit=1&design=redesign (see redesign-editor-preview.js);
 * this page holds state and saves via /api/admin/homepage-design.php.
 * Settings only affect the redesign homepage — while HOMEPAGE_DESIGN=classic
 * in prod, edits stay invisible to visitors until the flag flips.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageTitle = 'Homepage Design';
$pageSubtitle = 'Fonts, hero background and stickers for the redesign homepage';

include __DIR__ . '/components/header.php';

require_once APP_ROOT . '/inc/settings.php';
require_once APP_ROOT . '/inc/homepage_fonts.php';
require_once APP_ROOT . '/inc/homepage_bg_photos.php';

$design = getHomepageDesign();
$photos = listHomepageBgPhotos();

$fonts = [];
foreach (HOMEPAGE_FONTS as $slug => $f) {
    $fonts[$slug] = ['family' => $f['family'], 'stack' => $f['stack'], 'weight' => $f['weight'], 'group' => $f['group'], 'note' => $f['note']];
}

// hero background presets — same list as the original design workbench
$presets = [
    ['n' => 'Sky (default)', 'v' => 'default', 'sw' => '#45A3D9'],
    ['n' => 'Ocean teal',    'v' => '#0E6E7E', 'sw' => '#0E6E7E'],
    ['n' => 'Sunset coral',  'v' => '#EE6C4D', 'sw' => '#EE6C4D'],
    ['n' => 'Forest',        'v' => '#0B7D2C', 'sw' => '#0B7D2C'],
    ['n' => 'Sunny',         'v' => '#F4C430', 'sw' => '#F4C430'],
    ['n' => 'Cream',         'v' => '#F5EFDF', 'sw' => '#F5EFDF'],
];

$config = [
    'design'   => $design,
    'defaults' => homepageDesignDefaults(),
    'photos'   => $photos,
    'fonts'    => $fonts,
    'presets'  => $presets,
];
?>

<!-- Display fonts for the picker previews -->
<link href="<?= h(redesignFontsUrl($design['font'], true)) ?>" rel="stylesheet">

<style>
    .hp-font-btn{display:flex;align-items:baseline;justify-content:space-between;gap:8px;width:100%;text-align:left;
        border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;margin-bottom:4px;background:#fff;cursor:pointer}
    .hp-font-btn:hover{border-color:#93c5fd;background:#f8fafc}
    .hp-font-btn.active{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 1px #2563eb}
    .hp-font-btn .fn{font-size:1.05rem;color:#111827}
    .hp-font-btn .fc{font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;white-space:nowrap}
    .hp-sw{width:26px;height:26px;border-radius:50%;border:2px solid #d1d5db;cursor:pointer;padding:0}
    .hp-sw.active{border-color:#111827;box-shadow:0 0 0 2px #fbbf24}
    .hp-thumb{aspect-ratio:1.3;border-radius:6px;border:2px solid #e5e7eb;background-size:cover;background-position:center;cursor:pointer;padding:0}
    .hp-thumb.active{border-color:#2563eb;box-shadow:0 0 0 1px #2563eb}
    .hp-mode{flex:1;border:1px solid #e5e7eb;border-radius:7px;padding:6px;cursor:pointer;font-size:.7rem;font-weight:600;
        text-transform:uppercase;letter-spacing:.06em;background:#fff;color:#374151}
    .hp-mode.active{background:#2563eb;border-color:#2563eb;color:#fff}
    #hpPreviewWrap{position:relative;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
    #hpPreview{width:100%;height:78vh;min-height:540px;border:0;display:block}
</style>

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <p class="text-sm text-gray-500">
        Changes preview live below and go public only when you hit
        <span class="font-semibold text-gray-700">Save &amp; publish</span>
        (and only on the redesign homepage).
        A homepage photo published in <a href="/admin/page-heroes" class="font-semibold text-blue-600 hover:text-blue-800">Headers &amp; Heroes</a> takes priority over this background.
    </p>
    <div class="flex items-center gap-3">
        <span id="hpStatus" class="text-sm text-gray-500"></span>
        <button id="hpReset" type="button" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset to default</button>
        <button id="hpSave" type="button" disabled class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">Save &amp; publish</button>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr,340px] gap-6 items-start">

    <!-- Live preview -->
    <div id="hpPreviewWrap">
        <iframe id="hpPreview" src="/?design=redesign&rdedit=1" title="Homepage preview"></iframe>
    </div>

    <!-- Controls -->
    <div class="space-y-6">

        <!-- Background -->
        <section class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Hero background</h2>
            <div class="flex gap-2 mb-3">
                <button id="hpModeColor" type="button" class="hp-mode">Colour</button>
                <button id="hpModePhoto" type="button" class="hp-mode">Photo</button>
            </div>
            <div id="hpColorRow">
                <div id="hpSwatches" class="flex gap-2 mb-3"></div>
                <label class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                    Custom <input type="color" id="hpCustomColor" value="#45A3D9" class="w-9 h-6 border-0 p-0 cursor-pointer rounded">
                </label>
            </div>
            <div id="hpPhotoRow" style="display:none">
                <div id="hpThumbs" class="grid grid-cols-3 gap-2 mb-3"></div>
                <button id="hpUploadBtn" type="button" class="w-full mb-3 px-3 py-2 rounded-lg border border-dashed border-gray-300 text-sm font-semibold text-gray-500 hover:border-blue-400 hover:text-blue-600">Upload photo</button>
                <input id="hpUploadInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Photo opacity
                    <input id="hpOpacity" type="range" min="20" max="100" value="100" class="w-full block mt-1">
                </label>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400">Darken
                    <input id="hpDarken" type="range" min="0" max="70" value="32" class="w-full block mt-1">
                </label>
            </div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mt-4">Paper texture
                <input id="hpTexture" type="range" min="0" max="75" value="22" class="w-full block mt-1">
            </label>
        </section>

        <!-- Stickers -->
        <section class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Stickers</h2>
            <div class="flex flex-wrap gap-2 mb-3">
                <button data-add-sticker="note" type="button" class="px-3 py-1.5 rounded-md border text-xs font-semibold text-gray-600 hover:bg-gray-50">＋ Note</button>
                <button data-add-sticker="circle" type="button" class="px-3 py-1.5 rounded-md border text-xs font-semibold text-gray-600 hover:bg-gray-50">＋ Circle</button>
                <button data-add-sticker="banner" type="button" class="px-3 py-1.5 rounded-md border text-xs font-semibold text-gray-600 hover:bg-gray-50">＋ Banner</button>
                <button data-add-sticker="star" type="button" class="px-3 py-1.5 rounded-md border text-xs font-semibold text-gray-600 hover:bg-gray-50">＋ Star</button>
                <button data-add-sticker="swash" type="button" class="px-3 py-1.5 rounded-md border text-xs font-semibold text-gray-600 hover:bg-gray-50">＋ Swash</button>
                <button id="hpClearStickers" type="button" class="px-3 py-1.5 rounded-md bg-red-50 border border-red-200 text-xs font-semibold text-red-600 hover:bg-red-100">Clear all</button>
            </div>
            <div id="hpSelPanel"></div>
        </section>

        <!-- Display font -->
        <section class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2">Display font</h2>
            <p class="text-xs text-gray-400 mb-2">Used for the big headlines across the redesign.</p>
            <div id="hpFontList" class="max-h-[46vh] overflow-y-auto pr-1"></div>
        </section>

    </div>
</div>

<script type="application/json" id="hpConfig"><?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script src="/assets/js/admin-homepage-design.js?v=1" <?= cspNonceAttr() ?>></script>

<?php include __DIR__ . '/components/footer.php'; ?>
