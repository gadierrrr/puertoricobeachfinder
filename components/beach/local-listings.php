<?php
/**
 * Beach Detail — Featured Local Listings (sponsored)
 *
 * Expects: $beach, $lang
 * With no active listings it renders only the compact "Advertise" teaser,
 * which is the sales funnel for the feature.
 */
require_once APP_ROOT . '/inc/listings.php';

echo renderLocalListingsSection($beach, $lang, 'classic');
