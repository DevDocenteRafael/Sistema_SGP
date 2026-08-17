<?php

return [
    'status' => [
        'Planejamento',
        'Em Andamento',
        'Concluído',
        'Arquivado',
    ],

    /**
     * Configuração de prazos para o semáforo (indicador visual)
     * Será definido pela CPED após análise
     * Por enquanto, valores padrão para fins de demonstração
     */
    'prazos' => [
        'dias_verde' => 30,      // Verde: mais de X dias até prazo
        'dias_amarelo' => 15,    // Amarelo: entre X e Y dias até prazo
        'dias_vermelho' => 0,    // Vermelho: menos de X dias até prazo
    ],
];
