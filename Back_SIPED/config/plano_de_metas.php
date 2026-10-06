<?php

/**
 * Plano de Metas (SPEC 05).
 *
 * Áreas/origens do planejamento citadas pela CPED. A estrutura detalhada de cada área
 * (ex.: planejamento 2027 por DN/DEF/CPED/Planejamento Estratégico) ainda depende do
 * cliente — não inventar campos, metas ou percentuais além destes.
 */
return [
    'areas' => [
        'Planejamento Estratégico',
        'DN',
        'DEF',
        'CPED',
    ],

    // Grafias aceitas na planilha/formulário => área oficial.
    'aliases_area' => [
        'planejamento estrategico' => 'Planejamento Estratégico',
        'pe' => 'Planejamento Estratégico',
        'dn' => 'DN',
        'departamento nacional' => 'DN',
        'def' => 'DEF',
        'cped' => 'CPED',
    ],
];
