<?php

/**
 * Segmentos (eixos tecnológicos da planilha).
 * Lista derivada de config/segmentos.php — não duplicar nomes aqui.
 */
$mapa = require __DIR__.'/segmentos.php';

return array_values(array_unique(array_merge(...array_values($mapa))));
