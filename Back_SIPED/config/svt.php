<?php

/**
 * Integração com o SVT — Sistema de Visitas Técnicas (SPEC 07).
 *
 * O SVT é um sistema separado (fluxo Instrutor → Coordenação → CPAD → DEP → NULOG).
 * O SIPED recebe as visitas por API e mostra a página de Visitas Técnicas para consulta.
 * Contrato: docs/integracao-svt.md
 */
return [
    /*
    | De onde vêm as visitas:
    | - local: cadastro e importação de planilha no próprio SIPED (comportamento atual,
    |          até o SVT entrar em produção);
    | - svt:   as visitas vêm só do SVT; no SIPED a página fica somente para consulta.
    | Visitas recebidas do SVT nunca são editadas no SIPED, em nenhum dos modos.
    */
    'modo' => env('VISITAS_TECNICAS_ORIGEM', 'local'),

    // Token compartilhado com o SVT. Sem token, o endpoint de integração fica desligado.
    'token' => env('SVT_INTEGRACAO_TOKEN'),

    // Página inicial do SVT (atalho em Sistemas de Apoio) e link direto para uma visita ({id}).
    'base_url' => env('SVT_BASE_URL'),
    'visita_url' => env('SVT_VISITA_URL'),

    // Máximo de visitas por chamada.
    'lote_maximo' => 500,

    // Etapas do fluxo do SVT (informativo; o SVT é a fonte da verdade).
    'etapas' => ['Instrutor', 'Coordenação', 'CPAD', 'DEP', 'NULOG'],

    // Identificador gravado em source_system.
    'sistema' => 'svt',
];
