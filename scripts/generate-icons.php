<?php
/**
 * PWA Icon Generator
 * Run once to generate app icons: php scripts/generate-icons.php
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/../public/assets/icons';

// Beach emoji/icon colors
$bgColor = [37, 99, 235];    // Blue-600
$sandColor = [253, 224, 71]; // Yellow-300
$waveColor = [14, 165, 233]; // Sky-500

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);

    // Enable alpha blending
    imagealphablending($image, true);
    imagesavealpha($image, true);

    // Colors
    $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    $sand = imagecolorallocate($image, $sandColor[0], $sandColor[1], $sandColor[2]);
    $wave = imagecolorallocate($image, $waveColor[0], $waveColor[1], $waveColor[2]);
    $white = imagecolorallocate($image, 255, 255, 255);

    // Fill background
    imagefilledrectangle($image, 0, 0, $size, $size, $bg);

    // Draw beach scene
    $margin = $size * 0.15;
    $innerSize = $size - ($margin * 2);

    // Sand (bottom arc)
    $sandY = $size * 0.55;
    imagefilledellipse($image, $size/2, $size + $size*0.3, $size * 1.2, $size, $sand);

    // Wave lines
    $waveY = $size * 0.45;
    $waveHeight = $size * 0.08;
    for ($i = 0; $i < 3; $i++) {
        $y = $waveY + ($i * $waveHeight * 1.5);
        $amplitude = $size * 0.05;

        // Draw wavy line
        for ($x = $margin; $x < $size - $margin; $x++) {
            $yOffset = sin(($x / $size) * 4 * M_PI) * $amplitude;
            imagesetpixel($image, $x, $y + $yOffset, $white);
            imagesetpixel($image, $x, $y + $yOffset + 1, $white);
            if ($size >= 128) {
                imagesetpixel($image, $x, $y + $yOffset + 2, $white);
            }
        }
    }

    // Sun
    $sunRadius = $size * 0.12;
    $sunX = $size * 0.75;
    $sunY = $size * 0.25;
    imagefilledellipse($image, $sunX, $sunY, $sunRadius * 2, $sunRadius * 2, $sand);

    // Palm tree (simplified)
    if ($size >= 96) {
        $trunkX = $size * 0.3;
        $trunkBottom = $size * 0.6;
        $trunkTop = $size * 0.25;
        $brown = imagecolorallocate($image, 139, 90, 43);
        $green = imagecolorallocate($image, 34, 197, 94);

        // Trunk
        imagesetthickness($image, max(2, $size * 0.03));
        imageline($image, $trunkX, $trunkBottom, $trunkX - $size*0.02, $trunkTop, $brown);

        // Palm leaves
        $leafLen = $size * 0.15;
        for ($angle = -60; $angle <= 60; $angle += 30) {
            $rad = deg2rad($angle - 90);
            $endX = $trunkX - $size*0.02 + cos($rad) * $leafLen;
            $endY = $trunkTop + sin($rad) * $leafLen;
            imageline($image, $trunkX - $size*0.02, $trunkTop, $endX, $endY, $green);
        }
    }

    // Save
    $filename = "$outputDir/icon-{$size}x{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Generated: $filename\n";
}

// Generate shortcut icons
$shortcuts = [
    'quiz' => [139, 92, 246],   // Purple
    'heart' => [239, 68, 68],   // Red
];

foreach ($shortcuts as $name => $color) {
    $size = 96;
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, true);
    imagesavealpha($image, true);

    $bg = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $white = imagecolorallocate($image, 255, 255, 255);

    imagefilledrectangle($image, 0, 0, $size, $size, $bg);

    if ($name === 'heart') {
        // Simple heart shape
        $cx = $size / 2;
        $cy = $size / 2;
        $heartSize = $size * 0.3;
        imagefilledellipse($image, $cx - $heartSize/2, $cy - $heartSize/4, $heartSize, $heartSize, $white);
        imagefilledellipse($image, $cx + $heartSize/2, $cy - $heartSize/4, $heartSize, $heartSize, $white);
        $points = [
            $cx - $heartSize, $cy,
            $cx, $cy + $heartSize,
            $cx + $heartSize, $cy
        ];
        imagefilledpolygon($image, $points, 3, $white);
    } else {
        // Question mark for quiz
        $font = 5;
        $text = '?';
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($image, $font, ($size - $textWidth) / 2, ($size - $textHeight) / 2 - 5, $text, $white);

        // Circle around it
        imageellipse($image, $size/2, $size/2, $size * 0.6, $size * 0.6, $white);
    }

    $filename = "$outputDir/{$name}-96x96.png";
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Generated: $filename\n";
}

echo "\nAll icons generated successfully!\n";
