<?php
/**
 * Backfill responsive WebP variants for legacy beach images.
 *
 * Generates -400w, -800w, -1200w WebP variants in /public/images/thumbnails/
 * for all images in /public/images/beaches/.
 *
 * Usage: php scripts/backfill-responsive-images.php [--dry-run]
 *
 * Uses PHP GD for consistency with inc/image-optimizer.php.
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$baseDir = __DIR__ . '/../public/images/beaches';
$thumbDir = __DIR__ . '/../public/images/thumbnails';

if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$sizes = [
    ['suffix' => '-400w',  'width' => 400,  'quality' => 78],
    ['suffix' => '-800w',  'width' => 800,  'quality' => 80],
    ['suffix' => '-1200w', 'width' => 1200, 'quality' => 82],
];

// Collect source images — prefer .webp over .jpg/.jpeg
$sources = [];
foreach (glob($baseDir . '/*.{webp,jpg,jpeg}', GLOB_BRACE) as $file) {
    $basename = pathinfo($file, PATHINFO_FILENAME);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Prefer WebP source if we haven't seen this basename, or upgrade from jpg/jpeg
    if (!isset($sources[$basename]) || $ext === 'webp') {
        $sources[$basename] = $file;
    }
}

$processed = 0;
$skipped = 0;
$errors = 0;
$totalSaved = 0;
$total = count($sources);

echo ($dryRun ? "[DRY RUN] " : "") . "Processing $total source images...\n\n";

foreach ($sources as $basename => $sourcePath) {
    // Check if all 3 variants already exist
    $allExist = true;
    foreach ($sizes as $size) {
        $outPath = $thumbDir . '/' . $basename . $size['suffix'] . '.webp';
        if (!file_exists($outPath)) {
            $allExist = false;
            break;
        }
    }

    if ($allExist) {
        $skipped++;
        continue;
    }

    if ($dryRun) {
        echo "  Would process: $basename (" . basename($sourcePath) . ")\n";
        $processed++;
        continue;
    }

    // Load source image
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $source = match ($ext) {
        'webp' => @imagecreatefromwebp($sourcePath),
        'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
        default => null,
    };

    if (!$source) {
        echo "  ✗ Failed to load: $basename\n";
        $errors++;
        continue;
    }

    // Auto-rotate JPEG based on EXIF
    if (in_array($ext, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        if ($exif && isset($exif['Orientation'])) {
            $source = match ((int)$exif['Orientation']) {
                3 => imagerotate($source, 180, 0),
                6 => imagerotate($source, -90, 0),
                8 => imagerotate($source, 90, 0),
                default => $source,
            };
        }
    }

    $origW = imagesx($source);
    $origH = imagesy($source);
    $originalSize = filesize($sourcePath);
    $variantsSaved = 0;

    foreach ($sizes as $size) {
        $outPath = $thumbDir . '/' . $basename . $size['suffix'] . '.webp';

        // Skip if this specific variant already exists
        if (file_exists($outPath)) {
            continue;
        }

        // Don't upscale — if source is smaller than target, use source dimensions
        $targetW = min($size['width'], $origW);
        $ratio = $targetW / $origW;
        $targetH = max(1, intval($origH * $ratio));

        $resized = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $targetW, $targetH, $transparent);
        imagealphablending($resized, true);

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
        imagewebp($resized, $outPath, $size['quality']);
        imagedestroy($resized);

        if (file_exists($outPath)) {
            $variantsSaved += filesize($outPath);
        }
    }

    imagedestroy($source);

    $savings = $originalSize * 3 - $variantsSaved; // vs serving original 3x
    $totalSaved += max(0, $savings);
    $processed++;

    echo "  ✓ $basename ({$origW}x{$origH})\n";
}

// Set permissions
if (!$dryRun) {
    exec("chown -R www-data:www-data " . escapeshellarg($thumbDir));
    exec("chmod -R 755 " . escapeshellarg($thumbDir));
}

echo "\n=== Summary ===\n";
echo "Processed:  $processed\n";
echo "Skipped:    $skipped (already had all variants)\n";
echo "Errors:     $errors\n";
if (!$dryRun) {
    echo "Est. bandwidth saved per page load: ~" . round($totalSaved / max(1, $processed) / 1024) . " KB avg\n";
}
echo "\nVariants saved to: $thumbDir\n";
