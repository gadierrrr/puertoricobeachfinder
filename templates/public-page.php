<?php
/**
 * Public page template.
 * Copy this file when creating a new public-facing page.
 */

require_once __DIR__ . '/../inc/helpers.php';

$pageTitle = $pageTitle ?? 'New Public Page';
$pageDescription = $pageDescription ?? 'Describe this page for search engines and social previews.';
$pageTheme = $pageTheme ?? 'home';
$skipMapCSS = $skipMapCSS ?? true;
$skipMapScripts = $skipMapScripts ?? true;
$skipAppScripts = $skipAppScripts ?? false;

$pageShellMode = 'start';
include __DIR__ . '/../components/page-shell.php';
?>

<section class="ui-hero">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl md:text-5xl font-bold"><?= h($pageTitle) ?></h1>
        <p class="mt-4 text-lg text-white/85 max-w-3xl"><?= h($pageDescription) ?></p>
    </div>
</section>

<section class="px-4 sm:px-6 py-10">
    <div class="max-w-5xl mx-auto ui-surface p-6 md:p-8">
        <p class="text-base md:text-lg leading-relaxed">
            Replace this section with your page content. Use `.ui-card`, `.ui-chip`, `.ui-btn-primary`, and `.ui-btn-secondary`
            to keep visual consistency with the homepage design system.
        </p>
    </div>
</section>

<?php
$pageShellMode = 'end';
include __DIR__ . '/../components/page-shell.php';
