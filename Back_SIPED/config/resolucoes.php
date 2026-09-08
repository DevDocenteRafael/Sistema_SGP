<?php

return [
    'vigencia_anos' => 5,
    'alerta_preventivo_meses' => 6,
    'alerta_critico_meses' => 1,
    'status' => [
        'vigente',
        'atencao',
        'critico',
        'vencida',
        'concluida',
    ],
    'categorias' => [
        'Normativa',
        'Operacional',
        'Regulamentação',
        'Interna',
    ],
    'setores' => [
        'CPED',
        'Gabinete',
        'Coordenação',
        'Diretoria',
    ],
    'semaforo' => [
        'verde' => 'vigente',
        'amarelo' => 'atencao',
        'vermelho' => 'critico',
        'vencida' => 'vencida',
    ],
];
