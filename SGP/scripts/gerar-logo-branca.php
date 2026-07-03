<?php

$origem = __DIR__ . '/../public/IMG/Logo-Senac.png';
$destino = __DIR__ . '/../public/IMG/Logo-Senac-branco.png';

if (! file_exists($origem)) {
    fwrite(STDERR, "Arquivo não encontrado: {$origem}\n");
    exit(1);
}

$src = imagecreatefrompng($origem);
if ($src === false) {
    fwrite(STDERR, "Não foi possível ler o PNG.\n");
    exit(1);
}

$largura = imagesx($src);
$altura = imagesy($src);

$dest = imagecreatetruecolor($largura, $altura);
imagealphablending($dest, false);
imagesavealpha($dest, true);

$transparente = imagecolorallocatealpha($dest, 0, 0, 0, 127);
imagefill($dest, 0, 0, $transparente);

$branco = imagecolorallocatealpha($dest, 255, 255, 255, 0);

for ($x = 0; $x < $largura; $x++) {
    for ($y = 0; $y < $altura; $y++) {
        $rgb = imagecolorat($src, $x, $y);
        $alpha = ($rgb >> 24) & 0x7F;
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        if ($alpha >= 120) {
            continue;
        }

        if ($r + $g + $b > 40) {
            imagesetpixel($dest, $x, $y, $branco);
        }
    }
}

imagedestroy($src);
imagepng($dest, $destino);
imagedestroy($dest);

echo "Logo branca gerada: {$destino}\n";
