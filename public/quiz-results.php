<?php
/**
 * Shareable Quiz Results Page
 * URL: /quiz-results?token=...
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
if (isset($_COOKIE['BEACH_FINDER_SESSION']) && session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
    session_start();
}

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

function renderQuizResultsRedesignState(string $heading, string $description): void
{
    $locale = getCurrentLanguage();
    ?>
    <div class="rd rd-qresults">
        <div class="wrap qres-state managed-page-hero"<?= pageHeroAttributes('quiz-results') ?>>
            <p class="eyebrow"><?= h($locale === 'es' ? 'Resultados del quiz' : 'Beach quiz results') ?></p>
            <h1><?= h($heading) ?><span class="dot">.</span></h1>
            <p><?= h($description) ?></p>
            <div class="qres-actions">
                <a class="qres-btn qres-btn-coral" href="<?= h(routeUrl('quiz', $locale)) ?>"><?= h(__('quiz_results.take_quiz')) ?></a>
                <a class="qres-btn qres-btn-light" href="<?= h(routeUrl('best_beaches', $locale)) ?>"><?= h(__('quiz_results.browse_best')) ?></a>
            </div>
        </div>
    </div>
    <?php
}

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    $pageTitle = __('quiz_results.title');
    $pageDescription = __('quiz_results.no_quiz_desc');
    $redesignLayout = useRedesign();
    $bodyClasses = trim(($bodyClasses ?? '') . ' rd-tool');
    include APP_ROOT . '/components/header.php';
    if ($redesignLayout) {
        renderQuizResultsRedesignState(__('quiz_results.title'), __('quiz_results.no_quiz_desc'));
        include APP_ROOT . '/components/footer.php';
        exit;
    }
    ?>
    <section class="hero-gradient-dark managed-page-hero text-white py-12 md:py-16"<?= pageHeroAttributes('quiz-results') ?>>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h(__('quiz_results.title')) ?></h1>
            <p class="text-lg md:text-xl opacity-90"><?= h(__('quiz_results.no_quiz_desc')) ?></p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="/quiz" class="inline-flex items-center justify-center bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-5 py-2.5 rounded-lg font-semibold transition-colors">
                    <?= h(__('quiz_results.take_quiz')) ?>
                </a>
                <a href="/best-beaches" class="inline-flex items-center justify-center bg-warm-100 hover:bg-warm-200 border border-warm-200 px-5 py-2.5 rounded-lg font-medium transition-colors">
                    <?= h(__('quiz_results.browse_best')) ?>
                </a>
            </div>
        </div>
    </section>
    <?php
    include APP_ROOT . '/components/footer.php';
    exit;
}

$robotsOverride = 'noindex, nofollow, noarchive';
$row = queryOne('SELECT * FROM quiz_results WHERE token = :token', [':token' => $token]);
if (!$row) {
    http_response_code(404);
    $pageTitle = __('quiz_results.not_found_title');
    $pageDescription = __('quiz_results.not_found_desc');
    $redesignLayout = useRedesign();
    $bodyClasses = trim(($bodyClasses ?? '') . ' rd-tool');
    include APP_ROOT . '/components/header.php';
    if ($redesignLayout) {
        renderQuizResultsRedesignState(__('quiz_results.not_found_heading'), __('quiz_results.not_found_expired'));
        include APP_ROOT . '/components/footer.php';
        exit;
    }
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center"><h1 class="text-2xl font-bold text-warm-900 mb-3">' . h(__('quiz_results.not_found_heading')) . '</h1><p class="text-warm-500">' . h(__('quiz_results.not_found_expired')) . '</p></div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

$matches = json_decode((string)($row['matched_beaches'] ?? '[]'), true);
if (!is_array($matches) || empty($matches)) {
    http_response_code(404);
    $pageTitle = __('quiz_results.not_found_title');
    $pageDescription = __('quiz_results.not_found_desc');
    $redesignLayout = useRedesign();
    $bodyClasses = trim(($bodyClasses ?? '') . ' rd-tool');
    include APP_ROOT . '/components/header.php';
    if ($redesignLayout) {
        renderQuizResultsRedesignState(__('quiz_results.not_found_heading'), __('quiz_results.not_found_empty'));
        include APP_ROOT . '/components/footer.php';
        exit;
    }
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center"><h1 class="text-2xl font-bold text-warm-900 mb-3">' . h(__('quiz_results.not_found_heading')) . '</h1><p class="text-warm-500">' . h(__('quiz_results.not_found_empty')) . '</p></div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

$beachIds = [];
foreach ($matches as $m) {
    if (is_array($m) && !empty($m['id'])) {
        $beachIds[] = (string)$m['id'];
    }
}
$beachIds = array_values(array_unique(array_filter($beachIds)));

$beachesById = [];
if (!empty($beachIds)) {
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT * FROM beaches WHERE id IN ($placeholders) AND publish_status = 'published'", $beachIds) ?: [];
    foreach ($rows as $b) {
        $beachesById[$b['id']] = $b;
    }
}

$pageTitle = __('quiz_results.your_matches');
$pageDescription = __('quiz_results.matches_desc');
$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-tool');
$qrIsAuthed = isAuthenticated();
$qrSaveCount = count($beachIds);
$qrCsrf = $qrIsAuthed ? csrfToken() : '';
$qrAutoSave = ($qrIsAuthed && ($_GET['save'] ?? '') === '1');
$qrLang = getCurrentLanguage();
$qrIsEs = ($qrLang === 'es');
$qrLoginRedirect = '/quiz-results?token=' . rawurlencode($token) . '&save=1&src=quiz';
include APP_ROOT . '/components/header.php';

if ($redesignLayout) {
    include APP_ROOT . '/templates/redesign/quiz-results.php';
    include APP_ROOT . '/components/footer.php';
    exit;
}
?>

<section class="hero-gradient-dark managed-page-hero text-white py-12 md:py-16"<?= pageHeroAttributes('quiz-results') ?>>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h(__('quiz_results.your_matches')) ?></h1>
        <p class="text-lg md:text-xl opacity-90"><?= h(__('quiz_results.save_link')) ?></p>
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/quiz" class="inline-flex items-center justify-center bg-warm-100 hover:bg-warm-200 border border-warm-200 px-5 py-2.5 rounded-lg font-medium transition-colors">
                <?= h(__('quiz_results.retake')) ?>
            </a>
            <button type="button"
                    data-action="bfShareCurrentQuizResults"
                    data-bf-source="quiz_results"
                    class="inline-flex items-center justify-center bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-5 py-2.5 rounded-lg font-semibold transition-colors">
                <?= h(__('quiz_results.share')) ?>
            </button>
        </div>
    </div>
</section>

<?php // Quiz -> sign-up / keep-your-matches band (classic layout). ?>
<section class="py-6 bg-sand-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-ocean-900 text-white rounded-2xl p-6 sm:p-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg sm:text-xl font-bold">
                    <?= $qrIsAuthed
                        ? ($qrIsEs ? 'Guarda tus playas ideales' : 'Keep your beach matches')
                        : ($qrIsEs ? '¿Quieres guardar estos resultados?' : 'Want to keep these matches?') ?>
                </h2>
                <p class="text-white/70 text-sm mt-1">
                    <?= $qrIsAuthed
                        ? ($qrIsEs
                            ? ('Guarda estas ' . $qrSaveCount . ' playas en tus favoritas para no perderlas.')
                            : ('Save these ' . $qrSaveCount . ' beaches to your favorites so you never lose them.'))
                        : ($qrIsEs
                            ? ('Crea una cuenta gratis para guardar estas ' . $qrSaveCount . ' playas en tus favoritas.')
                            : ('Create a free account to save these ' . $qrSaveCount . ' beaches to your favorites.')) ?>
                </p>
            </div>
            <?php if ($qrIsAuthed): ?>
            <button type="button" id="qr-save-btn"
                    data-token="<?= h($token) ?>" data-csrf="<?= h($qrCsrf) ?>"
                    class="shrink-0 inline-flex items-center justify-center gap-2 bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-semibold transition-colors">
                <i data-lucide="heart" class="w-5 h-5"></i>
                <?= $qrIsEs ? ('Guardar ' . $qrSaveCount . ' en favoritas') : ('Save ' . $qrSaveCount . ' to favorites') ?>
            </button>
            <?php else: ?>
            <a href="<?= h(routeUrl('login', $qrLang)) ?>?redirect=<?= rawurlencode($qrLoginRedirect) ?>"
               class="shrink-0 inline-flex items-center justify-center gap-2 bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-semibold transition-colors">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <?= $qrIsEs ? 'Crear una cuenta gratis' : 'Create a free account' ?>
            </a>
            <?php endif; ?>
        </div>
        <div id="qr-save-msg" class="hidden mt-3 text-sm px-4 py-3 rounded-lg"></div>
    </div>
</section>

<section class="py-10 bg-sand-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-warm-50 border border-warm-200 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-warm-900 mb-4"><?= h(__('quiz_results.top_matches')) ?></h2>

            <div class="space-y-4">
                <?php foreach ($matches as $m):
                    $id = (string)($m['id'] ?? '');
                    $score = (int)($m['score'] ?? 0);
                    $reasons = $m['match_reasons'] ?? [];
                    $b = $id && isset($beachesById[$id]) ? $beachesById[$id] : null;
                    $slug = (string)($b['slug'] ?? ($m['slug'] ?? ''));
                    $name = (string)($b['name'] ?? ($m['name'] ?? __('beach.beach')));
                    $muni = (string)($b['municipality'] ?? ($m['municipality'] ?? ''));
                    $cover = getBeachImageUrl(is_array($b) ? $b : $m, 'medium');
                ?>
                <div class="flex gap-4 bg-warm-50 border border-warm-200 rounded-xl p-4"
                     data-bf-beach-id="<?= h($id) ?>"
                     data-bf-beach-slug="<?= h($slug) ?>"
                     data-bf-municipality="<?= h($muni) ?>"
                     data-bf-source="quiz_results">
                    <img src="<?= h($cover) ?>"
                         data-fallback-src="/images/beaches/placeholder-beach.webp"
                         alt="<?= h($name) ?>"
                         class="w-20 h-20 rounded-lg object-cover shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm text-warm-500"><?= h($muni) ?></div>
                                <div class="text-lg font-semibold text-warm-900"><?= h($name) ?></div>
                            </div>
                            <div class="text-sm font-bold text-sunset-400"><?= $score ?>%</div>
                        </div>
                        <?php if (is_array($reasons) && !empty($reasons)): ?>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <?php foreach (array_slice($reasons, 0, 4) as $reason): ?>
                            <span class="text-xs bg-sunset-400/10 text-sunset-400 px-2 py-0.5 rounded-full border border-sunset-400/20"><?= h((string)$reason) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="/beach/<?= h($slug) ?>"
                               class="inline-flex items-center gap-2 bg-warm-100 hover:bg-warm-200 text-warm-900 px-3 py-2 rounded-lg text-sm border border-warm-200 transition-colors">
                                <?= h(__('quiz_results.view_details')) ?>
                            </a>
                            <?php if ($b): ?>
                            <a href="<?= h(getDirectionsUrl($b)) ?>" target="_blank" rel="noopener noreferrer"
                               data-bf-track="directions"
                               class="inline-flex items-center gap-2 bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <?= h(__('quiz_results.directions')) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include APP_ROOT . '/components/footer.php'; ?>

<script <?= cspNonceAttr() ?>>
window.QR_STRINGS = <?= json_encode([
    'share_title' => __('quiz_results.share_title'),
    'share_text' => __('quiz_results.share_text'),
    'link_copied' => __('quiz_results.link_copied'),
    'share_error' => __('quiz_results.share_error'),
]) ?>;
async function bfShareCurrentQuizResults() {
    const url = window.location.href;
    const title = QR_STRINGS.share_title;
    const text = QR_STRINGS.share_text;

    if (typeof window.bfTrack === 'function') {
        window.bfTrack('share_click', { source: 'quiz_results' });
    }

    if (navigator.share) {
        try {
            await navigator.share({ title, text, url });
            return;
        } catch (e) {
            if (e && e.name === 'AbortError') return;
        }
    }

    try {
        await navigator.clipboard.writeText(url);
        if (typeof window.showToast === 'function') {
            window.showToast(QR_STRINGS.link_copied, 'success', 2500);
        }
    } catch (e) {
        if (typeof window.showToast === 'function') {
            window.showToast(QR_STRINGS.share_error, 'warning', 3500);
        }
    }
}

// Quiz -> favorites save (authed) + auto-save when arriving with ?save=1 after sign-up.
const QR_SAVE = <?= json_encode([
    'saving'    => $qrIsEs ? 'Guardando…' : 'Saving…',
    'saved'     => $qrIsEs ? 'Guardado ✓' : 'Saved ✓',
    'saved_msg' => $qrIsEs ? 'Tus playas se guardaron en favoritas.' : 'Your matches were saved to your favorites.',
    'error'     => $qrIsEs ? 'No se pudieron guardar. Inténtalo de nuevo.' : 'Could not save. Please try again.',
]) ?>;
(function(){
    const btn = document.getElementById('qr-save-btn');
    const msg = document.getElementById('qr-save-msg');
    if (!btn) return;
    let done = false;
    async function saveMatches(auto){
        if (done || btn.disabled) return;
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = QR_SAVE.saving;
        try {
            const fd = new FormData();
            fd.set('results_token', btn.dataset.token || '');
            if (btn.dataset.csrf) fd.set('csrf_token', btn.dataset.csrf);
            const res = await fetch('/api/favorites/bulk-add.php', { method: 'POST', body: fd });
            const payload = await res.json();
            if (!res.ok || !payload.success) throw new Error(payload.error || 'Save failed');
            done = true;
            btn.textContent = QR_SAVE.saved;
            if (msg){ msg.textContent = QR_SAVE.saved_msg; msg.className = 'mt-3 text-sm px-4 py-3 rounded-lg bg-green-50 text-green-700 border border-green-200'; }
            if (typeof window.showToast === 'function') window.showToast(QR_SAVE.saved, 'success', 3000);
            if (typeof window.bfTrack === 'function') window.bfTrack('A2_quiz_saved', { source: 'quiz_results', auto: !!auto });
            // Strip ?save=1 so a reload or a shared URL doesn't re-fire the save / analytics.
            try {
                const u = new URL(window.location.href);
                if (u.searchParams.has('save')) {
                    u.searchParams.delete('save');
                    window.history.replaceState(null, '', u.pathname + u.search + u.hash);
                }
            } catch (e) {}
        } catch (e) {
            btn.disabled = false; btn.innerHTML = prev;
            if (msg){ msg.textContent = QR_SAVE.error; msg.className = 'mt-3 text-sm px-4 py-3 rounded-lg bg-red-50 text-red-700 border border-red-200'; }
        }
    }
    btn.addEventListener('click', () => saveMatches(false));
<?php if ($qrAutoSave): ?>
    saveMatches(true);
<?php endif; ?>
})();
</script>
