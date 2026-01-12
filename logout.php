<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/inc/session.php';
session_start();
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

logout();

redirect('/?logged_out=1');
