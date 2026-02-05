<?php
/**
 * Application bootstrap.
 *
 * Defines path constants and loads environment + error handling.
 * This file should be required by all public entrypoints.
 */

if (defined('APP_BOOTSTRAP_INCLUDED')) {
    return;
}
define('APP_BOOTSTRAP_INCLUDED', true);

define('APP_ROOT', realpath(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');

require_once APP_ROOT . '/inc/bootstrap.php';
