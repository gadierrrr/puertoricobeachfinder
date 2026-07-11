<?php
/**
 * Guide page — Tours & Experiences (Viator affiliate).
 *
 * Expects: $guideToursSlug (guide slug from public/guides/), $lang
 * Renders nothing when the guide has no active placements in
 * guide_tour_placements.
 */
require_once APP_ROOT . '/inc/tours.php';

echo renderGuideToursSection((string) ($guideToursSlug ?? ''), (string) ($lang ?? 'en'));
