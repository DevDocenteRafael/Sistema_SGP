<?php

/**
 * Atalhos institucionais (somente URLs públicas — sem senhas).
 */
return [
    'links' => [
        [
            'key' => 'sei',
            'label' => 'SEI',
            'descricao' => 'Sistema Eletrônico de Informações — processos administrativos.',
            'url' => env('SISTEMA_APOIO_SEI_URL', 'https://seisenac.df.senac.br/sei/'),
            'placeholder' => false,
        ],
        [
            'key' => 'sig',
            'label' => 'SIG',
            'descricao' => 'Sistema Integrado de Gestão.',
            'url' => env('SISTEMA_APOIO_SIG_URL', 'https://cloud.plataforma.senac.br/'),
            'placeholder' => false,
        ],
        [
            'key' => 'sigin',
            'label' => 'SIGIN',
            'descricao' => 'Sistema de Gerenciamento de Instrutores.',
            'url' => env('SISTEMA_APOIO_SIGIN_URL', 'https://sigin.df.senac.br/'),
            'placeholder' => false,
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
