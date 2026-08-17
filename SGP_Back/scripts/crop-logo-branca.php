<?php

$paths = [
    __DIR__ . '/../resources/images/Logo-Senac-branco.png',
    __DIR__ . '/../public/IMG/Logo-Senac-branco.png',
];

$source = $paths[0];

if (! file_exists($source)) {
    fwrite(STDERR, "Arquivo não encontrado: {$source}\n");
    exit(1);
}

$img = imagecreatefrompng($source);
imagesavealpha($img, true);

$width = imagesx($img);
$height = imagesy($img);
$minX = $width;
$minY = $height;
$maxX = 0;
$maxY = 0;

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $rgba = imagecolorat($img, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;

        if ($alpha < 120 && ($r + $g + $b) > 20) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

if ($maxX <= $minX || $maxY <= $minY) {
    fwrite(STDERR, "Não foi possível detectar conteúdo na imagem.\n");
    exit(1);
}

$cropWidth = $maxX - $minX + 1;
$cropHeight = $maxY - $minY + 1;

$dest = imagecreatetruecolor($cropWidth, $cropHeight);
imagealphablending($dest, false);
imagesavealpha($dest, true);

$transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
imagefill($dest, 0, 0, $transparent);
imagecopy($dest, $img, 0, 0, $minX, $minY, $cropWidth, $cropHeight);

imagedestroy($img);

foreach ($paths as $path) {
    imagepng($dest, $path);
    echo "Salvo: {$path} ({$cropWidth}x{$cropHeight})\n";
}

imagedestroy($dest);
