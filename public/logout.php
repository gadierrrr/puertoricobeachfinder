<?php
/**
 * Logout Handler
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/auth.php';
require_once APP_ROOT . '/inc/helpers.php';

logout();

redirect('/?logged_out=1');
