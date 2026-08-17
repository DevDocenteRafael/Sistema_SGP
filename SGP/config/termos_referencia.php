<?php

return [
    'status' => [
        'Planejamento',
        'Em Andamento',
        'Concluído',
        'Arquivado',
    ],

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
