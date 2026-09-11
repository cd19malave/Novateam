<?php
$dir = __DIR__ . '/assets/icons';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$sizes = [72, 96, 128, 144, 192, 512];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    $bg   = imagecolorallocate($img, 15, 23, 42);
    $purple = imagecolorallocate($img, 109, 93, 252);
    $white  = imagecolorallocate($img, 255, 255, 255);
    $pink   = imagecolorallocate($img, 236, 72, 153);

    imagefilledrectangle($img, 0, 0, $size, $size, $bg);

    $cx = $size / 2;
    $cy = $size / 2;
    $r  = $size * 0.38;

    imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $purple);

    $fontSize = (int) ($size * 0.42);
    $font = 'C:/Windows/Fonts/arial.ttf';
    $text = 'E';

    $bbox = imagettfbbox($fontSize, 0, $font, $text);
    $tw = $bbox[2] - $bbox[0];
    $th = $bbox[1] - $bbox[3];
    $tx = $cx - $tw / 2;
    $ty = $cy + $th / 2 - $th * 0.1;

    imagettftext($img, $fontSize, 0, (int)$tx, (int)$ty, $white, $font, $text);

    $outFile = $dir . "/icon-{$size}.png";
    imagepng($img, $outFile);
    imagedestroy($img);
    echo "Created: icon-{$size}.png\n";
}
echo "Done! All icons generated.\n";
