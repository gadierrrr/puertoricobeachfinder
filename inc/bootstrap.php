<?php
/**
 * Common application bootstrap
 */

if (defined('BOOTSTRAP_PHP_INCLUDED')) {
    return;
}
define('BOOTSTRAP_PHP_INCLUDED', true);

require_once __DIR__ . '/env.php';
loadEnvFile();

require_once __DIR__ . '/error-handler.php';
registerErrorHandlers();

validateEnvironment();

error_reporting(E_ALL);
ini_set('display_errors', appDebug() ? '1' : '0');
ini_set('log_errors', '1');
