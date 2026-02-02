<?php
/**
 * Internationalization (i18n) System
 * Supports English and Spanish
 */

// Available languages
define('SUPPORTED_LANGUAGES', ['en', 'es']);
define('DEFAULT_LANGUAGE', 'en');

/**
 * Get current language from session/cookie/browser
 */
function getCurrentLanguage(): string {
    // Check session first
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGUAGES)) {
        return $_SESSION['lang'];
    }

    // Check cookie
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGUAGES)) {
        return $_COOKIE['lang'];
    }

    // Check browser preference
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, SUPPORTED_LANGUAGES)) {
            return $browserLang;
        }
    }

    return DEFAULT_LANGUAGE;
}

/**
 * Set language preference
 */
function setLanguage(string $lang): void {
    if (!in_array($lang, SUPPORTED_LANGUAGES)) {
        $lang = DEFAULT_LANGUAGE;
    }

    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => true,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}

/**
 * Load translations for current language
 */
function loadTranslations(): array {
    static $translations = null;

    if ($translations === null) {
        $lang = getCurrentLanguage();
        $file = __DIR__ . "/lang/{$lang}.php";

        if (file_exists($file)) {
            $translations = include $file;
        } else {
            // Fallback to English
            $translations = include __DIR__ . '/lang/en.php';
        }
    }

    return $translations;
}

/**
 * Get translated string
 *
 * @param string $key Translation key (dot notation supported)
 * @param array $params Replacement parameters
 * @return string Translated string or key if not found
 */
function __($key, array $params = []): string {
    $translations = loadTranslations();

    // Support dot notation for nested keys
    $keys = explode('.', $key);
    $value = $translations;

    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key; // Return key if translation not found
        }
    }

    if (!is_string($value)) {
        return $key;
    }

    // Replace parameters
    foreach ($params as $param => $replacement) {
        $value = str_replace(":{$param}", $replacement, $value);
    }

    return $value;
}

/**
 * Echo translated string (convenience function)
 */
function _e($key, array $params = []): void {
    echo __($key, $params);
}

/**
 * Get language name
 */
function getLanguageName(string $code): string {
    $names = [
        'en' => 'English',
        'es' => 'Español'
    ];
    return $names[$code] ?? $code;
}

/**
 * Get language flag emoji
 */
function getLanguageFlag(string $code): string {
    $flags = [
        'en' => '🇺🇸',
        'es' => '🇵🇷'
    ];
    return $flags[$code] ?? '🌐';
}

/**
 * Check if current language is RTL
 */
function isRTL(): bool {
    return false; // Neither English nor Spanish is RTL
}

/**
 * Get HTML lang attribute
 */
function getHtmlLang(): string {
    $lang = getCurrentLanguage();
    return $lang === 'es' ? 'es-PR' : 'en';
}
