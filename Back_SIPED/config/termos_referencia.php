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
     * Semáforo de três estados (verde / amarelo / vermelho).
     * Amarelo começa quando faltam até `dias_atencao` dias para o vencimento da Ata
     * (ou do prazo do TR, quando ainda não há Ata). Vermelho = vencido.
     * PENDENTE CPED: prazo oficial da janela de atenção — não tratar como regra institucional.
     */
    'prazos' => [
        'dias_atencao' => 30,
    ],
];
