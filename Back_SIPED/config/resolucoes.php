<?php

return [
    'vigencia_anos' => 5,
    // Início da janela de Atenção antes do fim da vigência.
    'alerta_preventivo_meses' => 6,
    // Semáforo: exatamente três estados, calculados pela data de fim da vigência.
    'status' => [
        'vigente',
        'atencao',
        'vencida',
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
        'vermelho' => 'vencida',
    ],
];
