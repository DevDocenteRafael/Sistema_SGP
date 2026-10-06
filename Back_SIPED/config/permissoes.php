<?php

/**
 * Níveis de acesso do SIPED.
 *
 * Root — dono do sistema: único perfil que cadastra, edita, inativa e reativa usuários.
 *        Tem também todo o acesso do Administrador.
 * Administrador — acesso a tudo (dados, importação, auditoria e restauração) e consulta
 *        dos usuários, sem alterá-los.
 * Editor — cria e altera dados do portfólio (sem gerenciar usuários).
 * Consultor — apenas consulta (leitura).
 */
return [

    'perfis' => [
        'Root',
        'Administrador',
        'Editor',
        'Consultor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissões por ação
    |--------------------------------------------------------------------------
    |
    | Cada ação lista os perfis autorizados. Funções não se sobrepõem:
    | - só o Root cadastra, edita, inativa e reativa usuários
    | - só Administrador e Editor alteram dados
    | - Consultor apenas consulta
    |
    */

    'acoes' => [
        'gerenciar_usuarios' => ['Root'],
        'consultar_usuarios' => ['Root', 'Administrador'],
        'gerenciar_root' => ['Root'],
        'editar_dados' => ['Root', 'Administrador', 'Editor'],
        'consultar_dados' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'importar_dados' => ['Root', 'Administrador', 'Editor'],
        'ver_relatorios' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'consultar_auditoria' => ['Root', 'Administrador'],
        'restaurar_registros' => ['Root', 'Administrador'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rotas do menu (front) por perfil
    |--------------------------------------------------------------------------
    */

    'menu' => [
        'inicio' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'dashboard' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'relatorios' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'importacoes' => ['Root', 'Administrador', 'Editor'],
        'auditoria' => ['Root', 'Administrador'],
        'cursos' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'ciclos' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'ciclos-portfolio' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'plano-de-metas' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'pca' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'controle-de-resolucoes' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'termos-de-referencia' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'eixos' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'visitas-tecnicas' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'horas-pedagogicas' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'acoes-extensivas' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'eventos' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'jornada-pedagogica' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'ferramentas' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'sistemas-apoio' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'unidades' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'estruturas-institucionais' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'cped' => ['Root', 'Administrador', 'Editor', 'Consultor'],
        'usuarios' => ['Root', 'Administrador'],
    ],
];
