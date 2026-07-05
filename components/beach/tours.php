<?php
/**
 * Beach Detail — Tours & Experiences (Viator affiliate)
 *
 * Expects: $beach, $lang
 * Renders nothing when no active tour campaigns match this beach's region.
 */
require_once APP_ROOT . '/inc/tours.php';

echo renderToursSection($beach, $lang, 'classic');
