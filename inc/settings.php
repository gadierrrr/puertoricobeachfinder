<?php
/**
 * Site settings — generic key/value store (site_settings table, migration 043)
 * plus the homepage design settings consumed by the redesign homepage and
 * edited from /admin/homepage-design.
 */

if (defined('SETTINGS_PHP_INCLUDED')) {
    return;
}
define('SETTINGS_PHP_INCLUDED', true);

require_once __DIR__ . '/db.php';

function getSetting(string $key, ?string $default = null): ?string {
    try {
        $row = queryOne('SELECT value FROM site_settings WHERE key = :key', [':key' => $key]);
    } catch (\Throwable $e) {
        return $default; // table missing (migration not run) — fall back
    }
    return $row ? $row['value'] : $default;
}

function setSetting(string $key, string $value): void {
    execute(
        "INSERT INTO site_settings (key, value, updated_at) VALUES (:key, :value, datetime('now'))
         ON CONFLICT(key) DO UPDATE SET value = :value, updated_at = datetime('now')",
        [':key' => $key, ':value' => $value]
    );
}

/**
 * Defaults reproduce the shipped redesign exactly: Alfa Slab One, the sky
 * gradient hero, grain at .22, no stickers.
 */
function homepageDesignDefaults(): array {
    return [
        'font'          => 'alfa-slab-one',
        'bg_mode'       => 'color',   // color | photo
        'bg_color'      => 'default', // 'default' = sky gradient, else hex
        'bg_photo'      => '',        // /uploads/admin/homepage/…
        'photo_opacity' => 100,       // 20–100
        'darken'        => 32,        // 0–70
        'texture'       => 22,        // 0–75 (grain opacity ×100)
        'stickers'      => [],        // [{type,x,y,rot,sc,color,text}]
    ];
}

/**
 * Current homepage design merged over defaults, sanitized so template code
 * can trust every field's type.
 */
function getHomepageDesign(): array {
    $defaults = homepageDesignDefaults();
    $raw = getSetting('homepage_design');
    if (!$raw) {
        return $defaults;
    }
    $saved = json_decode($raw, true);
    if (!is_array($saved)) {
        return $defaults;
    }
    return sanitizeHomepageDesign(array_merge($defaults, $saved));
}

/**
 * Clamp/whitelist every field of a design array. Used on read and on save.
 */
function sanitizeHomepageDesign(array $d): array {
    require_once __DIR__ . '/homepage_fonts.php';
    $defaults = homepageDesignDefaults();

    $out = [];
    $out['font'] = isValidHomepageFont((string) ($d['font'] ?? '')) ? (string) $d['font'] : $defaults['font'];
    $out['bg_mode'] = in_array($d['bg_mode'] ?? '', ['color', 'photo'], true) ? $d['bg_mode'] : 'color';

    $color = (string) ($d['bg_color'] ?? 'default');
    $out['bg_color'] = ($color === 'default' || preg_match('/^#[0-9a-fA-F]{6}$/', $color)) ? $color : 'default';

    $photo = (string) ($d['bg_photo'] ?? '');
    $out['bg_photo'] = preg_match('#^/uploads/admin/homepage/[a-z0-9._-]+\.(webp|jpe?g|png)$#i', $photo) ? $photo : '';
    if ($out['bg_mode'] === 'photo' && $out['bg_photo'] === '') {
        $out['bg_mode'] = 'color';
    }

    $out['photo_opacity'] = max(20, min(100, (int) ($d['photo_opacity'] ?? 100)));
    $out['darken'] = max(0, min(70, (int) ($d['darken'] ?? 32)));
    $out['texture'] = max(0, min(75, (int) ($d['texture'] ?? 22)));

    $stickers = [];
    if (is_array($d['stickers'] ?? null)) {
        foreach (array_slice($d['stickers'], 0, 12) as $s) {
            if (!is_array($s)) {
                continue;
            }
            $type = $s['type'] ?? '';
            if (!in_array($type, ['note', 'circle', 'banner', 'star', 'swash'], true)) {
                continue;
            }
            $color = (string) ($s['color'] ?? '#F7E14C');
            $stickers[] = [
                'type'  => $type,
                'x'     => max(-4, min(104, (float) ($s['x'] ?? 50))),
                'y'     => max(-8, min(112, (float) ($s['y'] ?? 44))),
                'rot'   => max(-45, min(45, (float) ($s['rot'] ?? 0))),
                'sc'    => max(0.4, min(2.5, (float) ($s['sc'] ?? 1))),
                'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#F7E14C',
                'text'  => mb_substr((string) ($s['text'] ?? ''), 0, 60),
            ];
        }
    }
    $out['stickers'] = $stickers;

    return $out;
}

/**
 * Whether the hero background needs dark (ink) text — light flat colors.
 */
function homepageHeroIsLight(array $design): bool {
    if ($design['bg_mode'] === 'photo' || $design['bg_color'] === 'default') {
        return false;
    }
    $hex = ltrim($design['bg_color'], '#');
    $lum = (0.299 * hexdec(substr($hex, 0, 2))
          + 0.587 * hexdec(substr($hex, 2, 2))
          + 0.114 * hexdec(substr($hex, 4, 2))) / 255;
    return $lum > 0.62;
}
