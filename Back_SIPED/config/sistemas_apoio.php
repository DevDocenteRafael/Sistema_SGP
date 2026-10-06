<?php

/**
 * Atalhos institucionais (somente links externos — sem senhas nem integração).
 * As URLs vêm de config/sistemas_externos.php (SEI_BASE_URL, SIG_BASE_URL, ...).
 */
return [
    'links' => [
        [
            'key' => 'sei',
            'label' => 'SEI',
            'descricao' => 'Sistema Eletrônico de Informações — processos administrativos.',
            'sistema' => 'sei', // URL em config/sistemas_externos.php
            'placeholder' => false,
        ],
        [
            'key' => 'sig',
            'label' => 'SIG',
            'descricao' => 'Sistema Integrado de Gestão.',
            'sistema' => 'sig', // URL em config/sistemas_externos.php
            'placeholder' => false,
        ],
        [
            'key' => 'sigin',
            'label' => 'SIGIN',
            'descricao' => 'Sistema de Gerenciamento de Instrutores.',
            'sistema' => 'sigin', // URL em config/sistemas_externos.php
            'placeholder' => false,
        ],
        [
            'key' => 'senac',
            'label' => 'Site Senac DF',
            'descricao' => 'Portal institucional do Senac Distrito Federal.',
            'sistema' => 'senac', // URL em config/sistemas_externos.php
            'placeholder' => false,
        ],
    ],
];
