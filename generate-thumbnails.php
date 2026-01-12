<?php
/**
 * Generate WebP thumbnails for beach images
 * Run: php generate-thumbnails.php
 */

$baseDir = __DIR__ . '/images';
$thumbDir = __DIR__ . '/images/thumbnails';
$width = 400; // Thumbnail width
$quality = 80; // WebP quality

if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

// Get all beach images
$sources = [
    $baseDir . '/beaches',
    $baseDir . '/uploads/2025/09',
    $baseDir . '/uploads/2025/10'
];

$processed = 0;
$skipped = 0;
$errors = 0;

foreach ($sources as $sourceDir) {
    if (!is_dir($sourceDir)) continue;
    
    $files = glob($sourceDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $thumbPath = $thumbDir . '/' . $filename . '.webp';
        
        // Skip if thumbnail already exists and is newer than source
        if (file_exists($thumbPath) && filemtime($thumbPath) >= filemtime($file)) {
            $skipped++;
            continue;
        }
        
        // Generate thumbnail using ImageMagick
        $cmd = sprintf(
            'convert %s -resize %dx -quality %d -strip %s 2>&1',
            escapeshellarg($file),
            $width,
            $quality,
            escapeshellarg($thumbPath)
        );
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0) {
            $processed++;
            $originalSize = filesize($file);
            $thumbSize = filesize($thumbPath);
            $savings = round((1 - $thumbSize / $originalSize) * 100);
            echo "✓ $filename.webp (saved {$savings}%)\n";
        } else {
            $errors++;
            echo "✗ Failed: $filename\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Processed: $processed\n";
echo "Skipped (up-to-date): $skipped\n";
echo "Errors: $errors\n";

// Set permissions
exec("chown -R www-data:www-data $thumbDir");
exec("chmod -R 755 $thumbDir");

echo "\nThumbnails saved to: $thumbDir\n";
