<?php

/**
 * Níveis de acesso do SIPED.
 *
 * Root — dono técnico/funcional: gerencia administradores, usuários, auditoria e restauração.
 *        Não pode ser alterado nem inativado por perfis inferiores.
 * Administrador — gerencia usuários (exceto Root), auditoria, importação e restauração.
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
    | - só o Administrador gerencia usuários
    | - só Administrador e Editor alteram dados
    | - Consultor apenas consulta
    |
    */

    'acoes' => [
        'gerenciar_usuarios' => ['Root', 'Administrador'],
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
