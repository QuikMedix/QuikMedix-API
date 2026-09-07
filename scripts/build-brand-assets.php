<?php

// Run with `php scripts/build-brand-assets.php` (requires GD).
// Preserve the original artwork while removing its black matte.
$root = dirname(__DIR__);
$source = imagecreatefrompng($root.'/resources/images/branding/quikmedix-original.png');
$output = $root.'/public/images/branding';
if (!is_dir($output)) {
    mkdir($output, 0755, true);
}

function transparentCanvas(int $width, int $height): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    return $image;
}

$width = imagesx($source);
$height = imagesy($source);
$light = transparentCanvas($width, $height);
$dark = transparentCanvas($width, $height);
$matteThreshold = 24;
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $pixel = imagecolorat($source, $x, $y);
        $red = ($pixel >> 16) & 255;
        $green = ($pixel >> 8) & 255;
        $blue = $pixel & 255;
        $peak = max($red, $green, $blue);
        if ($peak <= $matteThreshold) {
            continue;
        }

        // Undo the black matte at the antialiased silhouette, preserving the
        // opaque shading inside the capsule and the original letter shapes.
        $edge = false;
        for ($dy = -2; $dy <= 2 && !$edge; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $width || $ny >= $height) {
                    continue;
                }
                $neighbor = imagecolorat($source, $nx, $ny);
                if (max(($neighbor >> 16) & 255, ($neighbor >> 8) & 255, $neighbor & 255) <= $matteThreshold) {
                    $edge = true;
                    break;
                }
            }
        }
        $opacity = $edge ? ($peak - $matteThreshold) / (255 - $matteThreshold) : 1;
        $alpha = (int) round(127 * (1 - $opacity));
        $scale = $edge ? 255 / $peak : 1;
        $rgb = [(int) round($red * $scale), (int) round($green * $scale), (int) round($blue * $scale)];
        imagesetpixel($light, $x, $y, imagecolorallocatealpha($light, ...[...$rgb, $alpha]));

        // The light UI needs a charcoal Q and lettering. Keep the glossy
        // red/white capsule intact, and preserve every red brand element.
        $insideCapsule = ($x >= 520 && $x <= 755 && $y >= 280 && $y <= 352)
            || ($x >= 595 && $x <= 678 && $y >= 200 && $y <= 430);
        $neutral = max($red, $green, $blue) - min($red, $green, $blue) < 45;
        $darkRgb = !$insideCapsule && $neutral ? [42, 49, 66] : $rgb;
        imagesetpixel($dark, $x, $y, imagecolorallocatealpha($dark, ...[...$darkRgb, $alpha]));
    }
}
imagepng($light, $root.'/resources/images/branding/quikmedix-transparent.png', 9);
imagepng($dark, $root.'/resources/images/branding/quikmedix-transparent-dark.png', 9);

$crops = [
    'quikmedix-logo' => ['x' => 245, 'y' => 120, 'width' => 790, 'height' => 580],
    'quikmedix-wordmark' => ['x' => 245, 'y' => 510, 'width' => 790, 'height' => 190],
    'quikmedix-icon' => ['x' => 430, 'y' => 100, 'width' => 420, 'height' => 420],
];
foreach ($crops as $name => $bounds) {
    foreach (['' => $dark, '-light' => $light] as $suffix => $master) {
        $crop = imagecrop($master, $bounds);
        imagesavealpha($crop, true);
        imagepng($crop, $output.'/'.$name.$suffix.'.png', 9);
    }
}

$icon = imagecreatefrompng($output.'/quikmedix-icon.png');
$iconSizes = [16, 32, 48, 64, 128, 180, 192, 256, 512];
$pngs = [];
foreach ($iconSizes as $size) {
    $resized = transparentCanvas($size, $size);
    imagecopyresampled($resized, $icon, 0, 0, 0, 0, $size, $size, imagesx($icon), imagesy($icon));
    ob_start();
    imagepng($resized, null, 9);
    $pngs[$size] = ob_get_clean();
    file_put_contents($output.'/quikmedix-icon-'.$size.'.png', $pngs[$size]);
}
$lightIcon = imagecreatefrompng($output.'/quikmedix-icon-light.png');
foreach ([32, 192] as $size) {
    $resized = transparentCanvas($size, $size);
    imagecopyresampled($resized, $lightIcon, 0, 0, 0, 0, $size, $size, imagesx($lightIcon), imagesy($lightIcon));
    imagepng($resized, $output.'/quikmedix-icon-'.$size.'-light.png', 9);
}

// ICO containers use PNG payloads, retaining their transparent alpha channel.
$sizes = [16, 32, 48, 64, 128, 256];
$directory = pack('vvv', 0, 1, count($sizes));
$payload = '';
$offset = 6 + 16 * count($sizes);
foreach ($sizes as $size) {
    $data = $pngs[$size];
    $directory .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($data), $offset);
    $payload .= $data;
    $offset += strlen($data);
}
$ico = $directory.$payload;
foreach (['public/favicon.ico', 'public/images/favicon.ico', 'resources/images/favicon.ico', 'public/images/branding/quikmedix-favicon.ico'] as $path) {
    file_put_contents($root.'/'.$path, $ico);
}

// Keep legacy asset URLs safe for older templates and cached pages.
foreach (['public/images', 'resources/images'] as $path) {
    foreach (['logo-dark.png', 'logoprint.png'] as $name) {
        copy($output.'/quikmedix-wordmark.png', $root.'/'.$path.'/'.$name);
    }
    copy($output.'/quikmedix-wordmark-light.png', $root.'/'.$path.'/logo-light.png');
    copy($output.'/quikmedix-logo.png', $root.'/'.$path.'/logo-cp.png');
    copy($output.'/quikmedix-icon.png', $root.'/'.$path.'/logo-sm.png');
}
foreach (['logo' => 'quikmedix-wordmark', 'icon' => 'quikmedix-icon'] as $name => $asset) {
    $bounds = $crops[$asset];
    $data = base64_encode(file_get_contents($output.'/'.$asset.'.png'));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 '.$bounds['width'].' '.$bounds['height'].'" role="img"><title>QuikMedix</title><image width="'.$bounds['width'].'" height="'.$bounds['height'].'" xlink:href="data:image/png;base64,'.$data.'"/></svg>'.PHP_EOL;
    file_put_contents($root.'/public/images/'.$name.'.svg', $svg);
}

echo "Transparent QuikMedix brand assets rebuilt from the original upload.\n";
