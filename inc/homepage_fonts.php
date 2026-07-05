<?php
/**
 * Display-font registry for the redesign homepage font picker
 * (/admin/homepage-design). All faces are Google Fonts — the same list the
 * original design-workbench mockup offered. `gf` is the families= param for
 * fonts.googleapis.com/css2; `weight` is applied to the headline so variable
 * families render at display weight.
 */

if (defined('HOMEPAGE_FONTS_INCLUDED')) {
    return;
}
define('HOMEPAGE_FONTS_INCLUDED', true);

const HOMEPAGE_FONTS = [
    'alfa-slab-one'       => ['family' => 'Alfa Slab One',       'stack' => "'Alfa Slab One',Georgia,serif",          'gf' => 'Alfa+Slab+One',                  'weight' => 400, 'group' => 'Sign-painter', 'note' => 'heavy slab'],
    'bungee'              => ['family' => 'Bungee',              'stack' => "'Bungee',sans-serif",                    'gf' => 'Bungee',                         'weight' => 400, 'group' => 'Sign-painter', 'note' => 'signage'],
    'titan-one'           => ['family' => 'Titan One',           'stack' => "'Titan One',sans-serif",                 'gf' => 'Titan+One',                      'weight' => 400, 'group' => 'Sign-painter', 'note' => 'chunky'],
    'luckiest-guy'        => ['family' => 'Luckiest Guy',        'stack' => "'Luckiest Guy',cursive",                 'gf' => 'Luckiest+Guy',                   'weight' => 400, 'group' => 'Sign-painter', 'note' => 'hand-painted'],
    'bowlby-one-sc'       => ['family' => 'Bowlby One SC',       'stack' => "'Bowlby One SC',sans-serif",             'gf' => 'Bowlby+One+SC',                  'weight' => 400, 'group' => 'Sign-painter', 'note' => 'fat caps'],
    'lobster'             => ['family' => 'Lobster',             'stack' => "'Lobster',cursive",                      'gf' => 'Lobster',                        'weight' => 400, 'group' => 'Brush script', 'note' => 'sign script'],
    'pacifico'            => ['family' => 'Pacifico',            'stack' => "'Pacifico',cursive",                     'gf' => 'Pacifico',                       'weight' => 400, 'group' => 'Brush script', 'note' => 'beach script'],
    'kaushan-script'      => ['family' => 'Kaushan Script',      'stack' => "'Kaushan Script',cursive",               'gf' => 'Kaushan+Script',                 'weight' => 400, 'group' => 'Brush script', 'note' => 'brush'],
    'yellowtail'          => ['family' => 'Yellowtail',          'stack' => "'Yellowtail',cursive",                   'gf' => 'Yellowtail',                     'weight' => 400, 'group' => 'Brush script', 'note' => 'retro script'],
    'staatliches'         => ['family' => 'Staatliches',         'stack' => "'Staatliches',sans-serif",               'gf' => 'Staatliches',                    'weight' => 400, 'group' => 'Poster caps',  'note' => 'tall caps'],
    'anton'               => ['family' => 'Anton',               'stack' => "'Anton',sans-serif",                     'gf' => 'Anton',                          'weight' => 400, 'group' => 'Poster caps',  'note' => 'condensed'],
    'bebas-neue'          => ['family' => 'Bebas Neue',          'stack' => "'Bebas Neue',sans-serif",                'gf' => 'Bebas+Neue',                     'weight' => 400, 'group' => 'Poster caps',  'note' => 'ticket caps'],
    'fjalla-one'          => ['family' => 'Fjalla One',          'stack' => "'Fjalla One',sans-serif",                'gf' => 'Fjalla+One',                     'weight' => 400, 'group' => 'Poster caps',  'note' => 'condensed'],
    'unbounded'           => ['family' => 'Unbounded',           'stack' => "'Unbounded',sans-serif",                 'gf' => 'Unbounded:wght@700',             'weight' => 700, 'group' => 'Poster caps',  'note' => 'geo display'],
    'baloo-2'             => ['family' => 'Baloo 2',             'stack' => "'Baloo 2',system-ui,sans-serif",         'gf' => 'Baloo+2:wght@700',               'weight' => 700, 'group' => 'Rounded',      'note' => 'friendly'],
    'fredoka'             => ['family' => 'Fredoka',             'stack' => "'Fredoka',system-ui,sans-serif",         'gf' => 'Fredoka:wght@600',               'weight' => 600, 'group' => 'Rounded',      'note' => 'soft'],
    'quicksand'           => ['family' => 'Quicksand',           'stack' => "'Quicksand',system-ui,sans-serif",       'gf' => 'Quicksand:wght@700',             'weight' => 700, 'group' => 'Rounded',      'note' => 'geo round'],
    'bricolage-grotesque' => ['family' => 'Bricolage Grotesque', 'stack' => "'Bricolage Grotesque',sans-serif",       'gf' => 'Bricolage+Grotesque:wght@800',   'weight' => 800, 'group' => 'Grotesque',    'note' => 'modern'],
    'space-grotesk'       => ['family' => 'Space Grotesk',       'stack' => "'Space Grotesk',sans-serif",             'gf' => 'Space+Grotesk:wght@700',         'weight' => 700, 'group' => 'Grotesque',    'note' => 'techy'],
    'familjen-grotesk'    => ['family' => 'Familjen Grotesk',    'stack' => "'Familjen Grotesk',sans-serif",          'gf' => 'Familjen+Grotesk:wght@700',      'weight' => 700, 'group' => 'Grotesque',    'note' => 'neutral'],
    'dm-serif-display'    => ['family' => 'DM Serif Display',    'stack' => "'DM Serif Display',serif",               'gf' => 'DM+Serif+Display',               'weight' => 400, 'group' => 'Serif',        'note' => 'dramatic'],
    'yeseva-one'          => ['family' => 'Yeseva One',          'stack' => "'Yeseva One',serif",                     'gf' => 'Yeseva+One',                     'weight' => 400, 'group' => 'Serif',        'note' => 'warm'],
    'instrument-serif'    => ['family' => 'Instrument Serif',    'stack' => "'Instrument Serif',serif",               'gf' => 'Instrument+Serif',               'weight' => 400, 'group' => 'Serif',        'note' => 'editorial'],
    'fraunces'            => ['family' => 'Fraunces',            'stack' => "'Fraunces',serif",                       'gf' => 'Fraunces:opsz,wght@9..144,600',  'weight' => 600, 'group' => 'Serif',        'note' => 'classic'],
];

function isValidHomepageFont(string $slug): bool {
    return isset(HOMEPAGE_FONTS[$slug]);
}

function homepageFont(string $slug): array {
    return HOMEPAGE_FONTS[$slug] ?? HOMEPAGE_FONTS['alfa-slab-one'];
}

/**
 * Google Fonts stylesheet URL for the redesign pages: the always-needed body/
 * data faces plus the selected display face (or every picker face when the
 * admin editor needs to switch live).
 */
function redesignFontsUrl(string $displaySlug, bool $allDisplayFonts = false): string {
    $families = [
        'Saira+Semi+Condensed:wght@400;500;600;700',
        'Hanken+Grotesk:wght@400;500;600',
        'Kaushan+Script', // scripts/stickers always use it
    ];
    if ($allDisplayFonts) {
        foreach (HOMEPAGE_FONTS as $f) {
            $families[] = $f['gf'];
        }
    } else {
        $families[] = homepageFont($displaySlug)['gf'];
    }
    $families = array_unique($families);
    return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
}
