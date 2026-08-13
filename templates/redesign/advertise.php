<?php
/**
 * Redesign v2 advertising media kit.
 * Expects controller variables from public/advertise.php.
 */

$packageName = static function (array $package) use ($isEs): string {
    return (string) $package[$isEs ? 'name_es' : 'name_en'];
};
$packageDescription = static function (array $package) use ($isEs): string {
    return (string) $package[$isEs ? 'description_es' : 'description_en'];
};
$placementLabels = [
    'beach' => $isEs ? 'Página de playa' : 'Beach page',
    'guide' => $isEs ? 'Guía editorial' : 'Editorial guide',
    'collection' => $isEs ? 'Colección' : 'Collection',
];
$faqs = $isEs ? [
    ['¿Esto cambia el orden de las playas?', 'No. Una compra nunca cambia puntuaciones, rankings, recomendaciones ni resultados orgánicos. Los anuncios viven en espacios separados y rotulados.'],
    ['¿Qué incluye la creación?', 'Preparamos la imagen y el texto en español e inglés, y te enviamos una vista previa antes de publicar.'],
    ['¿Cuánto dura el acuerdo?', 'Los listados de una playa o grupo son mensuales con 30 días mínimos. Los espacios regionales, de guía y colección reservan inventario por un mínimo de tres meses.'],
    ['¿Qué reportamos?', 'Impresiones visibles válidas, clics, tasa de clics, acción, ubicación y fechas. Nunca compartimos identidades ni datos personales de visitantes.'],
    ['¿Qué ocurre si el espacio no está disponible?', 'Extendemos cada día perdido por una falla nuestra. Si se pierde más del 20% del mes, puedes elegir un crédito o reembolso prorrateado.'],
] : [
    ['Does payment change beach rankings?', 'No. A purchase never changes scores, rankings, recommendations, or organic results. Ads live in separate, clearly labeled inventory.'],
    ['What does creative production include?', 'We prepare the image and copy in English and Spanish, then send a preview for approval before publishing.'],
    ['How long is the agreement?', 'Single-beach and cluster listings are monthly with a 30-day minimum. Regional, guide, and collection inventory has a three-month minimum.'],
    ['What do you report?', 'Valid viewable impressions, clicks, click-through rate, action, placement, and dates. We never share visitor identities or personal data.'],
    ['What happens if the placement is unavailable?', 'We extend the campaign one day for every site-caused missed day. If more than 20% of a month is missed, you may choose a pro-rated credit or refund.'],
];
?>

<div class="rd rd-advertise">
  <section class="ad-hero">
    <div class="wrap ad-hero__grid">
      <div class="ad-hero__copy">
        <p class="eyebrow"><?= h($isEs ? 'Inventario local · Puerto Rico' : 'Local inventory · Puerto Rico') ?></p>
        <h1><?= h($isEs ? 'Aparece cuando el día de playa ya está en marcha' : 'Show up when the beach day is already in motion') ?><span>.</span></h1>
        <p class="ad-hero__lede"><?= h($isEs
            ? 'Espacios pagados para restaurantes, alquileres, tours, tiendas, hospedajes y servicios cerca de las playas que visitan tus clientes.'
            : 'Paid placements for restaurants, rentals, tours, shops, stays, and services near the beaches your customers are visiting.') ?></p>
        <div class="ad-hero__actions">
          <a class="btn coral" href="#packages"><?= h($isEs ? 'Ver espacios y precios' : 'See placements and pricing') ?></a>
          <a class="ad-text-link" href="#inquiry"><?= h($isEs ? 'Hablar sobre mi negocio' : 'Tell us about your business') ?> →</a>
        </div>
        <dl class="ad-coverage" aria-label="<?= h($isEs ? 'Cobertura verificada' : 'Verified coverage') ?>">
          <div><dt><?= number_format((int) $coverage['beaches']) ?></dt><dd><?= h($isEs ? 'perfiles de playa' : 'beach profiles') ?></dd></div>
          <div><dt><?= number_format((int) $coverage['municipalities']) ?></dt><dd><?= h($isEs ? 'municipios' : 'municipalities') ?></dd></div>
          <div><dt>EN + ES</dt><dd><?= h($isEs ? 'creatividad incluida' : 'creative included') ?></dd></div>
        </dl>
      </div>

      <div class="ad-hero__preview" aria-label="<?= h($isEs ? 'Ejemplo del espacio' : 'Placement preview') ?>">
        <div class="ad-preview__chrome">
          <span></span><span></span><span></span>
          <b><?= h($isEs ? 'PLANEA TU VISITA' : 'PLAN YOUR VISIT') ?></b>
        </div>
        <div class="ad-preview__beach">
          <div class="ad-preview__water"></div>
          <div class="ad-preview__copyline wide"></div>
          <div class="ad-preview__copyline"></div>
          <div class="ad-preview__slot">
            <div class="ad-preview__label"><?= h($isEs ? 'ANUNCIO PAGADO' : 'PAID ADVERTISEMENT') ?></div>
            <div class="ad-preview__business">
              <div class="ad-preview__thumb">🥭</div>
              <div><strong><?= h($isEs ? 'Tu negocio, cerca de aquí' : 'Your business, nearby') ?></strong><p><?= h($isEs ? 'Texto breve, bilingüe y útil.' : 'Short, bilingual, useful copy.') ?></p></div>
            </div>
            <div class="ad-preview__buttons"><span>WhatsApp</span><span><?= h($isEs ? 'Sitio web' : 'Website') ?></span></div>
          </div>
        </div>
        <p class="ad-preview__note"><?= h($isEs ? 'El anuncio no altera rankings ni contenido editorial.' : 'The ad never changes rankings or editorial content.') ?></p>
      </div>
    </div>
  </section>

  <section class="ad-atlas" aria-labelledby="atlas-title">
    <div class="wrap">
      <div class="ad-section-head">
        <p class="eyebrow"><?= h($isEs ? 'Mapa de inventario' : 'Inventory atlas') ?></p>
        <h2 id="atlas-title"><?= h($isEs ? 'Tres momentos, una regla clara: contexto primero' : 'Three moments, one clear rule: context first') ?></h2>
      </div>
      <div class="ad-atlas__chart">
        <svg viewBox="0 0 900 250" role="img" aria-label="<?= h($isEs ? 'Ruta de espacios pagados' : 'Paid placement route') ?>">
          <path class="ad-atlas__route" d="M85 155 C240 30 360 225 500 110 S710 30 820 145"/>
          <circle cx="110" cy="140" r="13"/><circle cx="485" cy="116" r="13"/><circle cx="800" cy="135" r="13"/>
        </svg>
        <article class="ad-atlas__stop ad-atlas__stop--beach"><span>01</span><h3><?= h($isEs ? 'Cerca de una playa' : 'Near a beach') ?></h3><p><?= h($isEs ? 'Negocios útiles para quien ya está planificando la visita.' : 'Useful businesses for someone already planning the visit.') ?></p></article>
        <article class="ad-atlas__stop ad-atlas__stop--guide"><span>02</span><h3><?= h($isEs ? 'Dentro de una guía' : 'Inside a guide') ?></h3><p><?= h($isEs ? 'Un solo patrocinador relevante por guía.' : 'One contextually relevant sponsor per guide.') ?></p></article>
        <article class="ad-atlas__stop ad-atlas__stop--collection"><span>03</span><h3><?= h($isEs ? 'En una colección' : 'On a collection') ?></h3><p><?= h($isEs ? 'Exclusivo y separado de la lista orgánica.' : 'Exclusive and separated from organic results.') ?></p></article>
      </div>
    </div>
  </section>

  <section id="packages" class="ad-packages" aria-labelledby="packages-title">
    <div class="wrap">
      <div class="ad-section-head ad-section-head--split">
        <div><p class="eyebrow"><?= h($isEs ? 'Tarifas de lanzamiento' : 'Launch rate card') ?></p><h2 id="packages-title"><?= h($isEs ? 'Escoge el alcance, no un paquete de impresiones' : 'Choose reach, not an impression bundle') ?></h2></div>
        <p><?= h($isEs ? 'Precios mensuales en USD. La primera mensualidad se paga antes de publicar.' : 'Monthly pricing in USD. The first month is prepaid before launch.') ?></p>
      </div>
      <div class="ad-package-grid">
        <?php foreach ($packages as $package):
          $slug = (string) $package['slug'];
          $isExclusive = !empty($package['exclusive']);
          $isRecommended = $slug === 'beach-cluster';
        ?>
        <article class="ad-package<?= $isRecommended ? ' is-recommended' : '' ?>">
          <?php if ($isRecommended): ?><div class="ad-package__flag"><?= h($isEs ? 'Mejor punto de partida' : 'Best starting point') ?></div><?php endif; ?>
          <div class="ad-package__type"><?= h($placementLabels[$package['placement_type']] ?? $package['placement_type']) ?></div>
          <h3><?= h($packageName($package)) ?></h3>
          <p><?= h($packageDescription($package)) ?></p>
          <div class="ad-package__price"><strong>$<?= number_format(((int) $package['price_cents']) / 100, 0) ?></strong><span><?= h($isEs ? '/mes' : '/month') ?></span></div>
          <ul>
            <li><?= h($isEs ? 'Imagen y texto bilingüe incluidos' : 'Bilingual image and copy included') ?></li>
            <li><?= h($isEs ? 'Reporte de vistas y clics válidos' : 'Valid view and click reporting') ?></li>
            <li><?= h($isExclusive ? ($isEs ? 'Inventario exclusivo' : 'Exclusive inventory') : ($isEs ? 'Hasta ' . (int) $package['included_units'] . ' ubicación(es)' : 'Up to ' . (int) $package['included_units'] . ' placement(s)')) ?></li>
          </ul>
          <div class="ad-package__term"><?= h((int) $package['minimum_term_months'] === 1
              ? ($isEs ? 'Mensual · mínimo 30 días' : 'Month-to-month · 30-day minimum')
              : ($isEs ? 'Reserva mínima de 3 meses' : 'Three-month minimum reservation')) ?></div>
          <a href="#inquiry" class="btn<?= $isRecommended ? ' coral' : '' ?>" data-ad-package-choice="<?= h($slug) ?>"><?= h($isEs ? 'Consultar este espacio' : 'Ask about this placement') ?></a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="ad-policy" aria-labelledby="policy-title">
    <div class="wrap ad-policy__grid">
      <div>
        <p class="eyebrow"><?= h($isEs ? 'Confianza antes que volumen' : 'Trust before volume') ?></p>
        <h2 id="policy-title"><?= h($isEs ? 'Pagado significa claramente pagado' : 'Paid means unmistakably paid') ?></h2>
        <p><?= h($isEs
            ? 'Cada espacio dice “Anuncio pagado”. No compramos rankings, no usamos datos personales para segmentar y no instalamos píxeles del anunciante.'
            : 'Every placement says “Paid advertisement.” Payment never buys rankings, we do not target using personal data, and advertiser pixels are not installed.') ?></p>
      </div>
      <ul class="ad-policy__list">
        <li><b>01</b><span><?= h($isEs ? 'Máximo dos tarjetas pagadas en una página de playa.' : 'No more than two paid cards on a beach page.') ?></span></li>
        <li><b>02</b><span><?= h($isEs ? 'Guías y colecciones tienen un solo patrocinador exclusivo.' : 'Guides and collections have one exclusive sponsor.') ?></span></li>
        <li><b>03</b><span><?= h($isEs ? 'Revisamos cada negocio, afirmación, enlace e imagen.' : 'We review every business, claim, link, and image.') ?></span></li>
        <li><b>04</b><span><?= h($isEs ? 'No prometemos ventas; sí prometemos colocación y medición transparentes.' : 'We do not promise sales; we do promise transparent placement and measurement.') ?></span></li>
      </ul>
    </div>
  </section>

  <section class="ad-process" aria-labelledby="process-title">
    <div class="wrap">
      <div class="ad-section-head"><p class="eyebrow"><?= h($isEs ? 'Proceso administrado' : 'Managed process') ?></p><h2 id="process-title"><?= h($isEs ? 'De consulta a publicación' : 'From inquiry to launch') ?></h2></div>
      <ol class="ad-process__steps">
        <li><span>1</span><h3><?= h($isEs ? 'Cuéntanos el objetivo' : 'Share the goal') ?></h3><p><?= h($isEs ? 'Respondemos dentro de dos días laborables.' : 'We respond within two business days.') ?></p></li>
        <li><span>2</span><h3><?= h($isEs ? 'Confirmamos inventario' : 'Confirm inventory') ?></h3><p><?= h($isEs ? 'Revisamos relevancia, fechas y exclusividad.' : 'We review relevance, dates, and exclusivity.') ?></p></li>
        <li><span>3</span><h3><?= h($isEs ? 'Creamos y aprobamos' : 'Create and approve') ?></h3><p><?= h($isEs ? 'Producimos ambas versiones y enviamos vista previa.' : 'We produce both languages and send a preview.') ?></p></li>
        <li><span>4</span><h3><?= h($isEs ? 'Publicamos y medimos' : 'Launch and measure') ?></h3><p><?= h($isEs ? 'Recibes un reporte claro de colocación, vistas y clics.' : 'You receive clear placement, view, and click reporting.') ?></p></li>
      </ol>
    </div>
  </section>

  <section id="inquiry" class="ad-inquiry" aria-labelledby="inquiry-title">
    <div class="wrap ad-inquiry__grid">
      <div class="ad-inquiry__intro">
        <p class="eyebrow"><?= h($isEs ? 'Consulta de inventario' : 'Inventory inquiry') ?></p>
        <h2 id="inquiry-title"><?= h($isEs ? 'Cuéntanos dónde encaja tu negocio' : 'Tell us where your business fits') ?></h2>
        <p><?= h($isEs ? 'No necesitas traer un anuncio terminado. Nosotros preparamos la imagen y el texto bilingüe.' : 'You do not need a finished ad. We prepare the image and bilingual copy.') ?></p>
        <div class="ad-inquiry__promise"><strong><?= h($isEs ? 'Respuesta:' : 'Response:') ?></strong> <?= h($isEs ? 'dentro de 2 días laborables' : 'within 2 business days') ?></div>
      </div>

      <div class="ad-inquiry__form-card">
        <?php if ($sent): ?>
        <div class="ad-notice ad-notice--success" data-ad-lead-success="1">
          <strong><?= h($isEs ? 'Consulta recibida.' : 'Inquiry received.') ?></strong>
          <span><?= h($isEs ? 'Revisaremos el inventario y te responderemos dentro de dos días laborables.' : 'We will review inventory and respond within two business days.') ?><?= $leadRef !== '' ? ' ' . h(($isEs ? 'Referencia: ' : 'Reference: ') . $leadRef) : '' ?></span>
        </div>
        <?php elseif ($errorCode !== ''): ?>
        <div class="ad-notice ad-notice--error"><strong><?= h($isEs ? 'Revisa el formulario.' : 'Check the form.') ?></strong><span><?= h($isEs ? 'Completa los campos requeridos e intenta otra vez.' : 'Complete the required fields and try again.') ?></span></div>
        <?php endif; ?>

        <form method="post" action="/api/advertise-lead.php" data-ad-lead-form>
          <input type="hidden" name="locale" value="<?= h($lang) ?>">
          <input type="hidden" name="source_page" value="<?= h($sourcePage) ?>">
          <input type="hidden" name="form_token" value="<?= h($formToken) ?>">
          <div class="ad-honeypot" aria-hidden="true"><label>Company website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></div>

          <div class="ad-form__grid">
            <label><span><?= h($isEs ? 'Nombre del negocio' : 'Business name') ?> *</span><input name="business_name" required maxlength="120" autocomplete="organization"></label>
            <label><span><?= h($isEs ? 'Tu nombre' : 'Your name') ?> *</span><input name="contact_name" required maxlength="120" autocomplete="name"></label>
            <label><span>Email *</span><input name="email" type="email" required maxlength="200" autocomplete="email"></label>
            <label><span><?= h($isEs ? 'Teléfono / WhatsApp' : 'Phone / WhatsApp') ?></span><input name="phone" type="tel" maxlength="30" autocomplete="tel"></label>
            <label><span><?= h($isEs ? 'Sitio web o Instagram' : 'Website or Instagram') ?></span><input name="website_url" type="text" maxlength="300" placeholder="https://… or @handle"></label>
            <label><span><?= h($isEs ? 'Categoría' : 'Category') ?> *</span><select name="category" required><?php foreach (ADVERTISING_ALLOWED_CATEGORIES as $key => $category): ?><option value="<?= h($key) ?>"><?= h($category['icon'] . ' ' . $category[$isEs ? 'es' : 'en']) ?></option><?php endforeach; ?></select></label>
          </div>

          <label><span><?= h($isEs ? 'Espacio de interés' : 'Placement of interest') ?> *</span><select id="ad-package-select" name="package_slug" required><?php foreach ($packages as $package): ?><option value="<?= h($package['slug']) ?>" <?= $package['slug'] === $selectedPackage ? 'selected' : '' ?>><?= h($packageName($package)) ?> — $<?= number_format(((int) $package['price_cents']) / 100, 0) ?>/<?= h($isEs ? 'mes' : 'mo') ?></option><?php endforeach; ?></select></label>
          <label><span><?= h($isEs ? 'Playa, guía, colección o región' : 'Beach, guide, collection, or region') ?></span><input name="target_details" maxlength="300" value="<?= h($beachName) ?>" placeholder="<?= h($isEs ? 'Ej. Rincón, Playa Flamenco, guía de snorkel' : 'e.g. Rincón, Flamenco Beach, snorkeling guide') ?>"></label>
          <label><span><?= h($isEs ? 'Objetivo y detalles' : 'Goal and details') ?></span><textarea name="message" rows="4" maxlength="2000" placeholder="<?= h($isEs ? '¿Qué quieres que haga la persona después de ver el anuncio?' : 'What should someone do after seeing the placement?') ?>"></textarea></label>
          <label class="ad-consent"><input type="checkbox" name="consent_contact" value="1" required><span><?= h($isEs ? 'Acepto que Puerto Rico Beach Finder me contacte sobre esta consulta. Esto no me suscribe a correos de mercadeo.' : 'I agree that Puerto Rico Beach Finder may contact me about this inquiry. This does not subscribe me to marketing email.') ?></span></label>
          <button class="btn coral ad-form__submit" type="submit"><?= h($isEs ? 'Solicitar disponibilidad' : 'Request availability') ?></button>
          <p class="ad-form__legal"><?= h($isEs ? 'Al enviar, aceptas nuestra' : 'By submitting, you agree to our') ?> <a href="<?= h(routeUrl('privacy', $lang)) ?>"><?= h($isEs ? 'Política de Privacidad' : 'Privacy Policy') ?></a> <?= h($isEs ? 'y' : 'and') ?> <a href="<?= h(routeUrl('terms', $lang)) ?>"><?= h($isEs ? 'Términos' : 'Terms') ?></a>.</p>
        </form>
      </div>
    </div>
  </section>

  <section class="ad-faq" aria-labelledby="faq-title"><div class="wrap"><div class="ad-section-head"><p class="eyebrow">FAQ</p><h2 id="faq-title"><?= h($isEs ? 'Antes de reservar' : 'Before you reserve') ?></h2></div><div class="ad-faq__grid"><?php foreach ($faqs as [$question, $answer]): ?><details><summary><?= h($question) ?></summary><p><?= h($answer) ?></p></details><?php endforeach; ?></div></div></section>
</div>

