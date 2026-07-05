<?php
/**
 * Advertise — sales page for featured local listings.
 *
 * Lead-gen v1: businesses submit the form, leads land in /admin/listings,
 * deals are closed manually. Pricing below is intro anchoring copy — edit
 * ADVERTISE_PRICING to change what's shown.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/listings.php';

$lang = getCurrentLanguage();
$isEs = $lang === 'es';

const ADVERTISE_PRICING = [
    'featured_monthly' => 25, // USD / month / beach — intro price shown on page
];

// Optional ?beach=<slug> preselects the beach the business came from.
$beachSlug = trim((string) ($_GET['beach'] ?? ''));
$beachName = '';
if ($beachSlug !== '') {
    $row = queryOne('SELECT name FROM beaches WHERE slug = :slug', [':slug' => $beachSlug]);
    $beachName = (string) ($row['name'] ?? '');
}

$sent = isset($_GET['sent']);
$error = isset($_GET['error']);

$pageTitle = $isEs
    ? 'Anuncia tu negocio — Playas de Puerto Rico'
    : 'Advertise Your Business | Puerto Rico Beach Finder';
$pageTitleNoBrandSuffix = true;
$pageDescription = $isEs
    ? 'Pon tu negocio frente a miles de visitantes que buscan playas cerca de ti. Listados destacados en las páginas de playa que la gente encuentra en Google.'
    : 'Put your business in front of thousands of beachgoers searching for beaches near you. Featured listings on the beach pages people find on Google.';

include APP_ROOT . '/components/header.php';

$price = ADVERTISE_PRICING['featured_monthly'];
?>

<section class="hero-gradient-dark text-white py-12 md:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4">
            <?= h($isEs ? 'Pon tu negocio frente a miles de beachgoers' : 'Put your business in front of thousands of beachgoers') ?>
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto">
            <?= h($isEs
                ? 'Las personas encuentran nuestras páginas de playa en Google cuando ya están planificando su visita. Tu negocio puede ser lo próximo que vean.'
                : 'People find our beach pages on Google while they are already planning their visit. Your business can be the next thing they see.') ?>
        </p>
    </div>
</section>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">

    <?php if ($sent): ?>
    <div class="mb-8 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
        <strong><?= h($isEs ? '¡Recibido!' : 'Got it!') ?></strong>
        <?= h($isEs
            ? 'Gracias por tu interés. Te contactaremos en 1–2 días laborables.'
            : "Thanks for your interest. We'll get back to you within 1–2 business days.") ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-8 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
        <?= h($isEs
            ? 'No pudimos enviar el formulario. Verifica los campos e intenta de nuevo.'
            : "We couldn't send the form. Check the fields and try again.") ?>
    </div>
    <?php endif; ?>

    <!-- Value props -->
    <div class="grid gap-6 md:grid-cols-3 mb-12">
        <div class="rounded-xl border border-stone-200 bg-white p-6">
            <div class="text-3xl mb-3">🔎</div>
            <h3 class="font-bold text-gray-900 mb-2"><?= h($isEs ? 'Tráfico con intención' : 'High-intent traffic') ?></h3>
            <p class="text-sm text-gray-600"><?= h($isEs
                ? 'Miles de visitantes cada semana buscando playas específicas — personas decidiendo a dónde ir hoy.'
                : 'Thousands of visitors every week searching for specific beaches — people deciding where to go today.') ?></p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-6">
            <div class="text-3xl mb-3">📍</div>
            <h3 class="font-bold text-gray-900 mb-2"><?= h($isEs ? 'Hiperlocal' : 'Hyperlocal') ?></h3>
            <p class="text-sm text-gray-600"><?= h($isEs
                ? 'Tu negocio aparece exactamente en las playas que te quedan cerca — no anuncios genéricos.'
                : 'Your business appears on exactly the beaches near you — not generic ads.') ?></p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-6">
            <div class="text-3xl mb-3">📊</div>
            <h3 class="font-bold text-gray-900 mb-2"><?= h($isEs ? 'Resultados medibles' : 'Measurable results') ?></h3>
            <p class="text-sm text-gray-600"><?= h($isEs
                ? 'Reporte mensual de vistas y clics a tu sitio, WhatsApp o teléfono.'
                : 'Monthly report of views and clicks to your website, WhatsApp, or phone.') ?></p>
        </div>
    </div>

    <!-- How it works + pricing -->
    <div class="grid gap-8 lg:grid-cols-2 mb-12">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h($isEs ? 'Cómo funciona' : 'How it works') ?></h2>
            <ol class="space-y-4">
                <?php
                $steps = $isEs
                    ? [
                        ['Cuéntanos de tu negocio', 'Completa el formulario con tu negocio y las playas que te interesan.'],
                        ['Creamos tu listado', 'Foto, descripción en español e inglés, y botones directos a tu WhatsApp, teléfono o sitio web.'],
                        ['Apareces en las páginas de playa', 'Tu listado se muestra en la sección "Favoritos locales" de las playas que elijas.'],
                    ]
                    : [
                        ['Tell us about your business', 'Fill out the form with your business and the beaches you care about.'],
                        ['We build your listing', 'Photo, bilingual description, and direct buttons to your WhatsApp, phone, or website.'],
                        ['You appear on beach pages', 'Your listing shows in the "Local favorites" section of the beaches you choose.'],
                    ];
                foreach ($steps as $i => [$t, $d]): ?>
                <li class="flex gap-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sunset-400 font-bold text-white"><?= $i + 1 ?></span>
                    <div>
                        <div class="font-semibold text-gray-900"><?= h($t) ?></div>
                        <div class="text-sm text-gray-600"><?= h($d) ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="rounded-2xl border-2 border-sunset-400 bg-white p-6 md:p-8 h-fit">
            <div class="text-xs font-bold uppercase tracking-wide text-sunset-600 mb-2"><?= h($isEs ? 'Precio de lanzamiento' : 'Intro pricing') ?></div>
            <div class="flex items-baseline gap-2 mb-4">
                <span class="text-4xl font-extrabold text-gray-900">$<?= (int) $price ?></span>
                <span class="text-gray-600"><?= h($isEs ? '/mes por playa' : '/month per beach') ?></span>
            </div>
            <ul class="space-y-2 text-sm text-gray-700 mb-6">
                <li>✓ <?= h($isEs ? 'Listado destacado con foto' : 'Featured listing with photo') ?></li>
                <li>✓ <?= h($isEs ? 'Botones a WhatsApp, teléfono, web o Instagram' : 'Buttons to WhatsApp, phone, website, or Instagram') ?></li>
                <li>✓ <?= h($isEs ? 'Descripción en español e inglés' : 'Bilingual description (Spanish + English)') ?></li>
                <li>✓ <?= h($isEs ? 'Reporte mensual de clics' : 'Monthly click report') ?></li>
                <li>✓ <?= h($isEs ? 'Sin contrato — cancela cuando quieras' : 'No contract — cancel anytime') ?></li>
            </ul>
            <p class="text-xs text-gray-500"><?= h($isEs
                ? 'Descuentos por múltiples playas o pago anual. Los detalles se acuerdan por email.'
                : 'Discounts for multiple beaches or annual billing. Details are worked out over email.') ?></p>
        </div>
    </div>

    <!-- Lead form -->
    <div id="form" class="rounded-2xl border border-stone-200 bg-white p-6 md:p-8 max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-900 mb-1"><?= h($isEs ? 'Empieza aquí' : 'Get started') ?></h2>
        <p class="text-sm text-gray-600 mb-6"><?= h($isEs
            ? 'Sin compromiso — te contactamos con los próximos pasos.'
            : 'No commitment — we reply with next steps.') ?></p>

        <form method="post" action="/api/advertise-lead.php" class="space-y-4">
            <input type="hidden" name="locale" value="<?= h($lang) ?>">
            <input type="hidden" name="source_page" value="<?= h($beachSlug !== '' ? '/advertise?beach=' . $beachSlug : '/advertise') ?>">
            <!-- Honeypot: bots fill this, humans never see it -->
            <div class="hidden" aria-hidden="true">
                <label>Company website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-business"><?= h($isEs ? 'Negocio *' : 'Business name *') ?></label>
                    <input id="ad-business" name="business_name" type="text" required maxlength="120"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-contact"><?= h($isEs ? 'Tu nombre' : 'Your name') ?></label>
                    <input id="ad-contact" name="contact_name" type="text" maxlength="120"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-email">Email *</label>
                    <input id="ad-email" name="email" type="email" required maxlength="200"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-phone"><?= h($isEs ? 'Teléfono / WhatsApp' : 'Phone / WhatsApp') ?></label>
                    <input id="ad-phone" name="phone" type="tel" maxlength="30"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-beaches"><?= h($isEs ? '¿Cerca de qué playa(s)?' : 'Near which beach(es)?') ?></label>
                <input id="ad-beaches" name="beaches_interest" type="text" maxlength="300"
                       value="<?= h($beachName) ?>"
                       placeholder="<?= h($isEs ? 'Ej: Balneario de Dorado, Playa Cerro Gordo' : 'e.g. Balneario de Dorado, Playa Cerro Gordo') ?>"
                       class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="ad-message"><?= h($isEs ? 'Cuéntanos de tu negocio' : 'Tell us about your business') ?></label>
                <textarea id="ad-message" name="message" rows="4" maxlength="2000"
                          class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-sm focus:border-sunset-400 focus:ring-1 focus:ring-sunset-400"></textarea>
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-sunset-500 hover:bg-sunset-600 px-6 py-3 font-bold text-white transition-colors">
                <?= h($isEs ? 'Enviar' : 'Send') ?>
            </button>
        </form>
    </div>
</div>

<?php include APP_ROOT . '/components/footer.php'; ?>
