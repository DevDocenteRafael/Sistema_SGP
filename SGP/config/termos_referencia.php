<?php

return [
    'status' => [
        'Planejamento',
        'Em Andamento',
        'Em tramitação (fora da CPED)',
        'Concluído',
        'Arquivado',
    ],

    'status_tramitacao_fora_cped' => 'Em tramitação (fora da CPED)',

    /**
     * Limiares visuais do semáforo (demonstração).
     * PENDENTE CPED: prazos oficiais de TR — não tratar estes números como regra institucional.
     */
    'prazos' => [
        'dias_verde' => 30,
        'dias_amarelo' => 15,
        'dias_vermelho' => 0,
    ],
];
