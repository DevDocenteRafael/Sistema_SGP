<?php

/**
 * URLs dos sistemas institucionais externos — fonte única (SPEC 04).
 *
 * São só links: o SIPED não tem integração por API com SEI, SIG ou SIGIN.
 * - *_BASE_URL: página inicial do sistema (atalho).
 * - SEI_PROCESSO_URL: modelo de link direto para um processo, com {processo}
 *   no lugar do número. Sem ele, o link abre a página inicial do SEI.
 * - SIG_CURSO_URL: modelo de link direto para um curso, com {codigo}. Só preencha
 *   quando a TI/SENAC fornecer o padrão oficial; sem ele o código SIG aparece como
 *   texto, com atalho para o SIG.
 *
 * As variáveis SISTEMA_APOIO_* antigas continuam aceitas como alternativa.
 */
return [
    'sei' => [
        'base_url' => env('SEI_BASE_URL', env('SISTEMA_APOIO_SEI_URL', 'https://seisenac.df.senac.br/sei/')),
        'processo_url' => env('SEI_PROCESSO_URL'),
    ],
    'sig' => [
        'base_url' => env('SIG_BASE_URL', env('SISTEMA_APOIO_SIG_URL', 'https://cloud.plataforma.senac.br/')),
        'curso_url' => env('SIG_CURSO_URL'),
    ],
    'sigin' => [
        'base_url' => env('SIGIN_BASE_URL', env('SISTEMA_APOIO_SIGIN_URL', 'https://sigin.df.senac.br/')),
    ],
    'svt' => [
        'base_url' => env('SVT_BASE_URL'),
    ],
    'senac' => [
        'base_url' => env('SENAC_SITE_URL', env('SISTEMA_APOIO_SENAC_URL', 'https://www.df.senac.br')),
    ],
];
