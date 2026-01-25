<?php
/**
 * Image Optimizer
 *
 * Handles WebP conversion, responsive size generation, and optimization
 * for admin-uploaded beach images.
 */

if (defined('IMAGE_OPTIMIZER_LOADED')) {
    return;
}
define('IMAGE_OPTIMIZER_LOADED', true);

// Quality settings for different sizes
define('IMAGE_QUALITY', [
    'original'    => 85,
    'large'       => 82,
    'medium'      => 80,
    'thumb'       => 78,
    'placeholder' => 60,
]);

// Maximum dimensions for each size
define('IMAGE_MAX_DIMENSIONS', [
    'original'    => 2400,
    'large'       => 1200,
    'medium'      => 800,
    'thumb'       => 400,
    'placeholder' => 20,
]);

// Allowed MIME types
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
]);

/**
 * Main entry point - process an uploaded image
 *
 * @param string $sourcePath Path to uploaded file
 * @param string $beachSlug Beach slug for filename
 * @param string $originalFilename Original uploaded filename
 * @return array Result with filenames, URLs, and optimization stats
 */
function optimizeImage(string $sourcePath, string $beachSlug, string $originalFilename): array
{
    // Validate file exists
    if (!file_exists($sourcePath)) {
        return ['error' => 'Source file not found'];
    }

    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $sourcePath);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['error' => 'Invalid image type. Allowed: JPEG, PNG, WebP, GIF'];
    }

    // Get original file size
    $originalSize = filesize($sourcePath);

    // Load image based on type
    $source = loadImage($sourcePath, $mimeType);
    if (!$source) {
        return ['error' => 'Failed to load image'];
    }

    // Auto-rotate based on EXIF orientation
    $source = autoRotateImage($source, $sourcePath, $mimeType);

    // Get dimensions
    $width = imagesx($source);
    $height = imagesy($source);

    // Generate unique filename base
    $uniqueId = substr(uniqid(), -8) . '_' . time();
    $safeSlug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($beachSlug));
    $baseFilename = $safeSlug . '_' . $uniqueId;

    // Define upload directory
    $uploadDir = __DIR__ . '/../uploads/admin/beaches/';

    // Generate all sizes
    $generatedFiles = [];
    $totalOptimizedSize = 0;

    foreach (IMAGE_MAX_DIMENSIONS as $sizeName => $maxDim) {
        $result = generateSize($source, $width, $height, $sizeName, $maxDim, $baseFilename, $uploadDir);
        if ($result) {
            $generatedFiles[$sizeName] = $result;
            $totalOptimizedSize += $result['size'];
        }
    }

    // Clean up source image resource
    imagedestroy($source);

    // Calculate optimization stats
    $savings = $originalSize - $generatedFiles['original']['size'];
    $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100) : 0;

    // Determine original format
    $originalFormat = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'unknown'
    };

    return [
        'success' => true,
        'filename' => $baseFilename,
        'original_filename' => $originalFilename,
        'original_format' => $originalFormat,
        'width' => $width,
        'height' => $height,
        'files' => $generatedFiles,
        'urls' => [
            'original' => '/uploads/admin/beaches/' . $baseFilename . '.webp',
            'large' => '/uploads/admin/beaches/' . $baseFilename . '_1200.webp',
            'medium' => '/uploads/admin/beaches/' . $baseFilename . '_800.webp',
            'thumb' => '/uploads/admin/beaches/' . $baseFilename . '_400.webp',
            'placeholder' => '/uploads/admin/beaches/' . $baseFilename . '_placeholder.webp',
        ],
        'optimization' => [
            'original_size' => $originalSize,
            'optimized_size' => $generatedFiles['original']['size'],
            'total_all_sizes' => $totalOptimizedSize,
            'savings_bytes' => $savings,
            'savings_percent' => $savingsPercent,
        ],
    ];
}

/**
 * Load image from file based on MIME type
 */
function loadImage(string $path, string $mimeType): ?GdImage
{
    return match($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        'image/gif' => @imagecreatefromgif($path),
        default => null
    };
}

/**
 * Auto-rotate image based on EXIF orientation
 */
function autoRotateImage(GdImage $image, string $path, string $mimeType): GdImage
{
    // EXIF only available for JPEG
    if ($mimeType !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return $image;
    }

    $exif = @exif_read_data($path);
    if (!$exif || !isset($exif['Orientation'])) {
        return $image;
    }

    $orientation = $exif['Orientation'];

    return match($orientation) {
        3 => imagerotate($image, 180, 0),
        6 => imagerotate($image, -90, 0),
        8 => imagerotate($image, 90, 0),
        default => $image
    };
}

/**
 * Generate a specific size variant
 */
function generateSize(GdImage $source, int $origWidth, int $origHeight, string $sizeName, int $maxDim, string $baseFilename, string $uploadDir): ?array
{
    // Calculate new dimensions
    $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);

    // For original size, only resize if larger than max
    if ($sizeName === 'original' && $ratio >= 1) {
        $newWidth = $origWidth;
        $newHeight = $origHeight;
    } else {
        $ratio = min($ratio, 1); // Never upscale
        $newWidth = max(1, intval($origWidth * $ratio));
        $newHeight = max(1, intval($origHeight * $ratio));
    }

    // Create resized image
    $resized = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    imagealphablending($resized, true);

    // Resample
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Determine filename suffix
    $suffix = match($sizeName) {
        'original' => '',
        'large' => '_1200',
        'medium' => '_800',
        'thumb' => '_400',
        'placeholder' => '_placeholder',
        default => '_' . $maxDim
    };

    $filename = $baseFilename . $suffix . '.webp';
    $filepath = $uploadDir . $filename;

    // Save as WebP
    $quality = IMAGE_QUALITY[$sizeName] ?? 80;
    $success = imagewebp($resized, $filepath, $quality);

    imagedestroy($resized);

    if (!$success || !file_exists($filepath)) {
        return null;
    }

    return [
        'filename' => $filename,
        'path' => $filepath,
        'size' => filesize($filepath),
        'width' => $newWidth,
        'height' => $newHeight,
    ];
}

/**
 * Delete all size variants of an image
 */
function deleteImageFiles(string $baseFilename): bool
{
    $uploadDir = __DIR__ . '/../uploads/admin/beaches/';
    $suffixes = ['', '_1200', '_800', '_400', '_placeholder'];
    $deleted = 0;

    foreach ($suffixes as $suffix) {
        $filepath = $uploadDir . $baseFilename . $suffix . '.webp';
        if (file_exists($filepath) && unlink($filepath)) {
            $deleted++;
        }
    }

    return $deleted > 0;
}

/**
 * Format file size for display
 */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Get all images for a beach
 */
function getBeachImages(string $beachId): array
{
    require_once __DIR__ . '/db.php';

    return query("
        SELECT * FROM beach_images
        WHERE beach_id = :beach_id
        ORDER BY position ASC, created_at ASC
    ", [':beach_id' => $beachId]);
}

/**
 * Get cover image for a beach
 */
function getBeachCoverImage(string $beachId): ?array
{
    require_once __DIR__ . '/db.php';

    $cover = queryOne("
        SELECT * FROM beach_images
        WHERE beach_id = :beach_id AND is_cover = 1
        LIMIT 1
    ", [':beach_id' => $beachId]);

    if (!$cover) {
        // Fall back to first image
        $cover = queryOne("
            SELECT * FROM beach_images
            WHERE beach_id = :beach_id
            ORDER BY position ASC, created_at ASC
            LIMIT 1
        ", [':beach_id' => $beachId]);
    }

    return $cover;
}

/**
 * Build image URLs from filename
 */
function buildImageUrls(string $filename): array
{
    $base = '/uploads/admin/beaches/' . $filename;
    return [
        'original' => $base . '.webp',
        'large' => $base . '_1200.webp',
        'medium' => $base . '_800.webp',
        'thumb' => $base . '_400.webp',
        'placeholder' => $base . '_placeholder.webp',
    ];
}
