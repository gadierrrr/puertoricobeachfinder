<?php
/**
 * Set Language API
 * Changes the user's language preference
 */

require_once __DIR__ . '/../inc/session.php';
session_start();
require_once __DIR__ . '/../inc/i18n.php';

header('Content-Type: application/json');

$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';

if (!in_array($lang, SUPPORTED_LANGUAGES)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
    exit;
}

setLanguage($lang);

echo json_encode([
    'success' => true,
    'language' => $lang,
    'name' => getLanguageName($lang)
]);
