<?php
/**
 * Redesign v2 — shareable quiz results.
 *
 * Expects: $matches, $beachesById, $beachIds, $token, $qrIsAuthed,
 * $qrSaveCount, $qrCsrf, $qrAutoSave, $qrLang, $qrIsEs, $qrLoginRedirect.
 */
$resultCount = count($matches);
$localizedQuizUrl = routeUrl('quiz', $qrLang);
?>
<div class="rd rd-qresults">
    <section class="qres-hero managed-page-hero"<?= pageHeroAttributes('quiz-results') ?>>
        <div class="wrap qres-hero-inner">
            <p class="eyebrow"><?= h($qrIsEs ? 'Tu plan de playa personalizado' : 'Your personalized beach plan') ?></p>
            <h1><?= h(__('quiz_results.your_matches')) ?><span class="dot">.</span></h1>
            <p class="qres-lede"><?= h(__('quiz_results.save_link')) ?></p>
            <div class="qres-proof" aria-label="<?= h($qrIsEs ? 'Resumen de resultados' : 'Results summary') ?>">
                <span><b><?= $resultCount ?></b> <?= h($qrIsEs ? 'recomendaciones' : 'recommendations') ?></span>
                <span><?= h($qrIsEs ? 'Basado en tus preferencias' : 'Matched to your preferences') ?></span>
                <span><?= h($qrIsEs ? 'Enlace privado para compartir' : 'Private shareable link') ?></span>
            </div>
            <div class="qres-actions">
                <a class="qres-btn qres-btn-light" href="<?= h($localizedQuizUrl) ?>"><?= h(__('quiz_results.retake')) ?></a>
                <button class="qres-btn qres-btn-coral"
                        type="button"
                        data-action="bfShareCurrentQuizResults"
                        data-bf-source="quiz_results"><?= h(__('quiz_results.share')) ?></button>
            </div>
        </div>
    </section>

    <div class="wrap qres-content">
        <section class="qres-keep" aria-labelledby="qresKeepHeading">
            <div>
                <p class="qres-kicker"><?= h($qrIsEs ? 'Guarda tu ruta' : 'Keep your shortlist') ?></p>
                <h2 id="qresKeepHeading">
                    <?= h($qrIsAuthed
                        ? ($qrIsEs ? 'Guarda tus playas ideales' : 'Keep your beach matches')
                        : ($qrIsEs ? '¿Quieres guardar estos resultados?' : 'Want to keep these matches?')) ?>
                </h2>
                <p>
                    <?= h($qrIsAuthed
                        ? ($qrIsEs
                            ? 'Guarda estas ' . $qrSaveCount . ' playas en tus favoritas para no perderlas.'
                            : 'Save these ' . $qrSaveCount . ' beaches to your favorites so you never lose them.')
                        : ($qrIsEs
                            ? 'Crea una cuenta gratis para guardar estas ' . $qrSaveCount . ' playas en tus favoritas.'
                            : 'Create a free account to save these ' . $qrSaveCount . ' beaches to your favorites.')) ?>
                </p>
            </div>
            <?php if ($qrIsAuthed): ?>
            <button type="button"
                    id="qr-save-btn"
                    class="qres-btn qres-btn-sun"
                    data-token="<?= h($token) ?>"
                    data-csrf="<?= h($qrCsrf) ?>">
                <?= h($qrIsEs ? 'Guardar ' . $qrSaveCount . ' en favoritas' : 'Save ' . $qrSaveCount . ' to favorites') ?>
            </button>
            <?php else: ?>
            <a class="qres-btn qres-btn-sun"
               href="<?= h(routeUrl('login', $qrLang)) ?>?redirect=<?= rawurlencode($qrLoginRedirect) ?>">
                <?= h($qrIsEs ? 'Crear una cuenta gratis' : 'Create a free account') ?>
            </a>
            <?php endif; ?>
        </section>
        <div id="qr-save-msg" class="qres-save-msg" role="status"></div>

        <section class="qres-matches" aria-labelledby="qresMatchesHeading">
            <div class="qres-section-head">
                <div>
                    <p class="eyebrow"><?= h($qrIsEs ? 'Tu lista corta' : 'Your shortlist') ?></p>
                    <h2 id="qresMatchesHeading"><?= h(__('quiz_results.top_matches')) ?></h2>
                </div>
                <p><?= h($qrIsEs ? 'Ordenadas por compatibilidad con tus respuestas.' : 'Ranked by how closely each beach fits your answers.') ?></p>
            </div>

            <div class="qres-grid">
                <?php foreach ($matches as $index => $match):
                    $id = (string) ($match['id'] ?? '');
                    $score = max(0, min(100, (int) ($match['score'] ?? 0)));
                    $reasons = is_array($match['match_reasons'] ?? null) ? $match['match_reasons'] : [];
                    $beach = $id !== '' && isset($beachesById[$id]) ? $beachesById[$id] : null;
                    $imageSource = is_array($beach) ? $beach : $match;
                    $slug = (string) ($beach['slug'] ?? ($match['slug'] ?? ''));
                    $name = (string) ($beach['name'] ?? ($match['name'] ?? __('beach.beach')));
                    $municipality = (string) ($beach['municipality'] ?? ($match['municipality'] ?? ''));
                    $cover = getBeachImageUrl($imageSource, 'medium');
                    $srcset = is_array($beach) ? getBeachImageSrcset($beach) : '';
                    $beachUrl = routeUrl('beach_detail', $qrLang, ['slug' => $slug]);
                ?>
                <article class="qres-card<?= $index === 0 ? ' qres-card-featured' : '' ?>"
                         data-bf-beach-id="<?= h($id) ?>"
                         data-bf-beach-slug="<?= h($slug) ?>"
                         data-bf-municipality="<?= h($municipality) ?>"
                         data-bf-source="quiz_results">
                    <a class="qres-photo" href="<?= h($beachUrl) ?>" aria-label="<?= h($name) ?>">
                        <img src="<?= h($cover) ?>"
                             <?php if ($srcset !== ''): ?>srcset="<?= h($srcset) ?>" sizes="(max-width: 700px) 100vw, 50vw"<?php endif; ?>
                             data-fallback-src="/images/beaches/placeholder-beach.webp"
                             alt="<?= h(getBeachImageAlt($imageSource, 'quiz result')) ?>"
                             loading="<?= $index < 2 ? 'eager' : 'lazy' ?>">
                        <span class="qres-rank">#<?= $index + 1 ?></span>
                        <span class="qres-score"><b><?= $score ?></b>% <?= h($qrIsEs ? 'afinidad' : 'match') ?></span>
                    </a>
                    <div class="qres-card-body">
                        <p class="qres-location"><?= h($municipality) ?></p>
                        <h3><a href="<?= h($beachUrl) ?>"><?= h($name) ?></a></h3>
                        <?php if ($reasons !== []): ?>
                        <div class="qres-reasons" aria-label="<?= h($qrIsEs ? 'Razones de compatibilidad' : 'Match reasons') ?>">
                            <?php foreach (array_slice($reasons, 0, 4) as $reason): ?>
                            <span><?= h((string) $reason) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="qres-card-actions">
                            <a class="qres-detail" href="<?= h($beachUrl) ?>"><?= h(__('quiz_results.view_details')) ?> →</a>
                            <?php if (is_array($beach)): ?>
                            <a class="qres-directions"
                               href="<?= h(getDirectionsUrl($beach)) ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               data-bf-track="directions"><?= h(__('quiz_results.directions')) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
window.QR_STRINGS = <?= json_encode([
    'share_title' => __('quiz_results.share_title'),
    'share_text' => __('quiz_results.share_text'),
    'link_copied' => __('quiz_results.link_copied'),
    'share_error' => __('quiz_results.share_error'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

window.bfShareCurrentQuizResults = async function () {
    const url = window.location.href;
    if (typeof window.bfTrack === 'function') {
        window.bfTrack('share_click', { source: 'quiz_results' });
    }
    if (navigator.share) {
        try {
            await navigator.share({ title: window.QR_STRINGS.share_title, text: window.QR_STRINGS.share_text, url });
            return;
        } catch (error) {
            if (error && error.name === 'AbortError') return;
        }
    }
    try {
        await navigator.clipboard.writeText(url);
        if (typeof window.showToast === 'function') {
            window.showToast(window.QR_STRINGS.link_copied, 'success', 2500);
        }
    } catch (error) {
        if (typeof window.showToast === 'function') {
            window.showToast(window.QR_STRINGS.share_error, 'warning', 3500);
        }
    }
};

(function () {
    const button = document.getElementById('qr-save-btn');
    const message = document.getElementById('qr-save-msg');
    if (!button) return;
    const strings = <?= json_encode([
        'saving' => $qrIsEs ? 'Guardando…' : 'Saving…',
        'saved' => $qrIsEs ? 'Guardado ✓' : 'Saved ✓',
        'saved_msg' => $qrIsEs ? 'Tus playas se guardaron en favoritas.' : 'Your matches were saved to your favorites.',
        'error' => $qrIsEs ? 'No se pudieron guardar. Inténtalo de nuevo.' : 'Could not save. Please try again.',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    let done = false;

    async function saveMatches(auto) {
        if (done || button.disabled) return;
        const previous = button.textContent;
        button.disabled = true;
        button.textContent = strings.saving;
        try {
            const form = new FormData();
            form.set('results_token', button.dataset.token || '');
            if (button.dataset.csrf) form.set('csrf_token', button.dataset.csrf);
            const response = await fetch('/api/favorites/bulk-add.php', { method: 'POST', body: form });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.error || 'Save failed');
            done = true;
            button.textContent = strings.saved;
            if (message) {
                message.textContent = strings.saved_msg;
                message.className = 'qres-save-msg is-success';
            }
            if (typeof window.showToast === 'function') window.showToast(strings.saved, 'success', 3000);
            if (typeof window.bfTrack === 'function') {
                window.bfTrack('A2_quiz_saved', { source: 'quiz_results', auto: !!auto });
            }
            const current = new URL(window.location.href);
            if (current.searchParams.has('save')) {
                current.searchParams.delete('save');
                window.history.replaceState(null, '', current.pathname + current.search + current.hash);
            }
        } catch (error) {
            button.disabled = false;
            button.textContent = previous;
            if (message) {
                message.textContent = strings.error;
                message.className = 'qres-save-msg is-error';
            }
        }
    }

    button.addEventListener('click', () => saveMatches(false));
    <?php if ($qrAutoSave): ?>saveMatches(true);<?php endif; ?>
})();
</script>
