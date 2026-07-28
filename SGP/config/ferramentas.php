<?php

/**
 * Catálogo de ferramentas do SGP (fonte da verdade no código).
 *
 * - internal: rota dentro do app
 * - external: abre URL em nova aba
 * - status available | coming_soon
 * - default_enabled: se a ferramenta está liberada (sem overrides no banco nesta versão)
 */
return [
    'catalogo' => [
        [
            'key' => 'kanban',
            'label' => 'Kanban',
            'description' => 'Organização das atividades e responsabilidades da equipe.',
            'type' => 'internal',
            'route' => '/app/ferramentas/kanban',
            'url' => null,
            'default_enabled' => true,
            'status' => 'available',
            'profiles' => ['Administrador', 'Editor', 'Consultor'],
            'icon' => 'kanban',
        ],
        [
            'key' => 'organograma',
            'label' => 'Organograma',
            'description' => 'Estrutura visual da CPED, cargos e responsáveis.',
            'type' => 'internal',
            'route' => '/app/ferramentas/organograma',
            'url' => null,
            'default_enabled' => false,
            'status' => 'coming_soon',
            'profiles' => ['Administrador', 'Editor', 'Consultor'],
            'icon' => 'organograma',
        ],
        [
            'key' => 'fluxograma',
            'label' => 'Fluxograma',
            'description' => 'Consulta aos fluxos oficiais dos processos da CPED.',
            'type' => 'internal',
            'route' => '/app/ferramentas/fluxograma',
            'url' => null,
            'default_enabled' => false,
            'status' => 'coming_soon',
            'profiles' => ['Administrador', 'Editor', 'Consultor'],
            'icon' => 'fluxograma',
        ],
        [
            'key' => 'microsoft_loop',
            'label' => 'Microsoft Loop',
            'description' => 'Atalho para espaços colaborativos da CPED no Loop.',
            'type' => 'external',
            'route' => null,
            'url' => 'https://loop.microsoft.com/',
            'default_enabled' => true,
            'status' => 'available',
            'profiles' => ['Administrador', 'Editor', 'Consultor'],
            'icon' => 'loop',
        ],
        [
            'key' => 'canva',
            'label' => 'Canva',
            'description' => 'Atalho para materiais visuais e apresentações.',
            'type' => 'external',
            'route' => null,
            'url' => 'https://www.canva.com/',
            'default_enabled' => true,
            'status' => 'available',
            'profiles' => ['Administrador', 'Editor', 'Consultor'],
            'icon' => 'canva',
        ],
    ],
];
