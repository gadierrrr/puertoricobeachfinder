<?php
/**
 * Guide i18n content loader.
 *
 * Body-content sections for each guide live in per-locale files:
 *   inc/lang/guides/{locale}/{slug}.php
 * Each file returns an associative array of HTML fragments keyed by section name.
 *
 * Falls back to English when the requested locale file does not exist.
 */

if (!defined('_GUIDE_I18N_LOADED')) {
    define('_GUIDE_I18N_LOADED', true);

    require_once __DIR__ . '/i18n.php';

    /**
     * Load guide body content sections for the current (or specified) locale.
     *
     * @param string      $slug   Guide slug (e.g. 'snorkeling-guide')
     * @param string|null $locale Override locale; defaults to getCurrentLanguage()
     * @return array<string, string> Keyed HTML fragments (empty array if no file exists)
     */
    function loadGuideContent(string $slug, ?string $locale = null): array
    {
        $locale = ($locale ?? getCurrentLanguage()) === 'es' ? 'es' : 'en';
        $base   = __DIR__ . '/lang/guides';

        $file = $base . '/' . $locale . '/' . $slug . '.php';
        if (!is_file($file) && $locale !== 'en') {
            $file = $base . '/en/' . $slug . '.php';
        }

        if (!is_file($file)) {
            return [];
        }

        $content = require $file;
        return is_array($content) ? $content : [];
    }
}
