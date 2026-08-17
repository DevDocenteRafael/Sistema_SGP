<?php

/**
 * Atalhos institucionais (somente URLs públicas — sem senhas).
 * Troque os placeholders quando a CPED informar os endereços oficiais.
 */
return [
    'links' => [
        [
            'key' => 'sei',
            'label' => 'SEI',
            'descricao' => 'Sistema Eletrônico de Informações — processos administrativos.',
            'url' => env('SISTEMA_APOIO_SEI_URL', 'https://sei.df.senac.br'),
            'placeholder' => true,
        ],
        [
            'key' => 'sig',
            'label' => 'SIG',
            'descricao' => 'Sistema de Gestão acadêmica e de cursos.',
            'url' => env('SISTEMA_APOIO_SIG_URL', 'https://sig.senac.br'),
            'placeholder' => true,
        ],
        [
            'key' => 'sigin',
            'label' => 'SIGIN',
            'descricao' => 'Sistema de informações gerenciais internas.',
            'url' => env('SISTEMA_APOIO_SIGIN_URL', 'https://sigin.senac.br'),
            'placeholder' => true,
        ],
        [
            'key' => 'senac',
            'label' => 'Site Senac DF',
            'descricao' => 'Portal institucional do Senac Distrito Federal.',
            'url' => env('SISTEMA_APOIO_SENAC_URL', 'https://www.df.senac.br'),
            'placeholder' => false,
        ],
    ],
];
