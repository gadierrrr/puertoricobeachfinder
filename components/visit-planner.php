<?php
require_once APP_ROOT . '/inc/search_landings.php';
$visitCopy = searchLandingCopy();
if (!$visitCopy || $visitCopy['intro'] === '') return;
$visitEs = getCurrentLanguage() === 'es';
?>
<section class="visit-planner" aria-labelledby="visit-planner-title">
    <h2 id="visit-planner-title"><?= h($visitCopy['heading']) ?></h2>
    <p><?= h($visitCopy['intro']) ?></p>
    <nav aria-label="<?= h($visitEs ? 'Planifica tu visita' : 'Plan your visit') ?>">
        <a href="<?= h(localizePath('/beaches-near-me', getCurrentLanguage())) ?>" data-bf-track="planner"><?= h($visitEs ? 'Buscar playas cerca de mí' : 'Find beaches near me') ?></a>
        <a href="<?= h(localizePath('/beaches/with-parking', getCurrentLanguage())) ?>" data-bf-track="planner"><?= h($visitEs ? 'Playas con estacionamiento' : 'Beaches with parking') ?></a>
        <a href="<?= h(localizePath('/guides/beach-safety-tips', getCurrentLanguage())) ?>" data-bf-track="planner"><?= h($visitEs ? 'Condiciones y seguridad' : 'Conditions and safety') ?></a>
    </nav>
</section>
