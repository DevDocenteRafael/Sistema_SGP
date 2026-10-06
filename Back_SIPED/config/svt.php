<?php

/**
 * Integração com o SVT — Sistema de Visitas Técnicas (SPEC 07).
 *
 * O SVT é um sistema separado (fluxo Instrutor → Núcleo Pedagógico → Coordenação DEP/CPED
 * → Direção Pedagógica → NULOG), conforme a ATA de 30/09/2026.
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

    // Etapas do fluxo (ATA de 30/09/2026). Informativo: o SVT é a fonte da verdade.
    'etapas' => [
        'Instrutor',
        'Núcleo Pedagógico da unidade',
        'Coordenação/área DEP/CPED',
        'Direção Pedagógica',
        'NULOG',
    ],

    // Limites de visitas por tipo de curso apresentados na ATA (referência; a regra
    // completa por curso ainda será fornecida e é aplicada no SVT).
    'limites_referencia' => [
        'Curso Técnico' => 4,
        'Qualificação' => 2,
        'Aperfeiçoamento' => 1,
        'Aprendizagem' => 4,
    ],

    // Identificador gravado em source_system.
    'sistema' => 'svt',
];
