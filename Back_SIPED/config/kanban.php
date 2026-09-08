<?php

/**
 * Configuração do Kanban da CPED.
 *
 * - quadro_padrao: slug do quadro carregado em GET /api/kanban
 * - colunas_padrao: usadas pelo seeder no primeiro setup
 */
return [
    'quadro_padrao' => 'cped',

    'colunas_padrao' => [
        [
            'titulo' => 'A Fazer',
            'position' => 0,
            'cor' => '#64748B',
        ],
        [
            'titulo' => 'Em Progresso',
            'position' => 1,
            'cor' => '#003F7D',
        ],
        [
            'titulo' => 'Concluído',
            'position' => 2,
            'cor' => '#16A34A',
        ],
    ],
];
