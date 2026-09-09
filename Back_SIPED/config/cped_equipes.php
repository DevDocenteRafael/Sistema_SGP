<?php

return [
    'tipos' => [
        'ordenador',
        'assistente',
        'responsavel',
        'instrutor',
        'administrativo',
    ],

    'tipos_filtro' => [
        ['value' => 'ordenador', 'label' => 'Ordenador'],
        ['value' => 'assistente', 'label' => 'Assistentes'],
        ['value' => 'responsavel', 'label' => 'Resp. de Eixo'],
        ['value' => 'instrutor', 'label' => 'Instrutores'],
        ['value' => 'administrativo', 'label' => 'Administrativos'],
    ],

    'setores_por_tipo' => [
        'ordenador' => ['CPED'],
        'assistente' => ['CPED', 'Secretaria Geral', 'Secretaria'],
        'responsavel' => [
            'Gastronomia e Turismo',
            'Ambiente e Saúde',
            'Gestão e Moda',
            'Tecnologia e Economia Criativa',
            'Beleza e Cuidado Pessoal',
        ],
        'instrutor' => [
            'Gastronomia e Turismo',
            'Ambiente e Saúde',
            'Gestão e Moda',
            'Tecnologia e Economia Criativa',
            'Beleza e Cuidado Pessoal',
        ],
        'administrativo' => [
            'CPED',
            'Secretaria Geral',
            'Secretaria',
            'TI / Sistemas',
            'Financeiro',
            'Patrimônio',
        ],
    ],

    'tipos_labels' => [
        'ordenador' => 'Ordenador',
        'assistente' => 'Assistente Administrativo',
        'responsavel' => 'Responsável de Eixo',
        'instrutor' => 'Instrutor',
        'administrativo' => 'Administrativo',
    ],

    'tipos_grupos' => [
        'ordenador' => 'Ordenador',
        'assistente' => 'Assistentes Administrativos',
        'responsavel' => 'Responsáveis de Eixo',
        'instrutor' => 'Instrutores Vinculados',
        'administrativo' => 'Administrativos Vinculados',
    ],

    'cores_tipo' => [
        'ordenador' => '#003F7D',
        'assistente' => '#5C6BC0',
        'responsavel' => '#E65100',
        'instrutor' => '#F57C00',
        'administrativo' => '#00796B',
    ],

    'eixos' => require __DIR__.'/eixos.php',

    'cores_eixo' => [
        'Gastronomia e Turismo' => '#E65100',
        'Beleza e Cuidado Pessoal' => '#AD1457',
        'Gestão e Moda' => '#B71C1C',
        'Tecnologia e Economia Criativa' => '#6A1B9A',
        'Ambiente e Saúde' => '#2E7D32',
    ],

    'setores' => array_values(array_unique(array_merge(
        require __DIR__.'/eixos.php',
        [
            'CPED',
            'Secretaria Geral',
            'Secretaria',
            'TI / Sistemas',
            'Financeiro',
            'Patrimônio',
        ]
    ))),
];
