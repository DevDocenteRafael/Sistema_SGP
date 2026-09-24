<?php

/**
 * Classificação de origem dos dados do SIPED.
 * Por padrão a entidade é somente leitura. CRUD é exceção administrativa.
 */
return [
    'mensagem_bloqueio' => 'Este registro é gerenciado por integração externa e não pode ser alterado diretamente no SIPED.',

    'administrativas' => [
        'usuarios',
        'cped',
        'ferramentas',
        'ciclos',
    ],

    'escritas_sistema' => [
        'logout',
    ],

    'rotas' => [
        'usuarios' => 'usuarios',
        'cped-equipes' => 'cped',
        'kanban' => 'ferramentas',
        'fluxogramas' => 'ferramentas',
        'organograma' => 'ferramentas',
        'carometro' => 'ferramentas',
        'ferramentas' => 'ferramentas',
        'ciclos' => 'ciclos',
        'portfolio-ciclos' => 'ciclos',
        'cursos' => 'cursos',
        'curso-execucoes' => 'ofertas',
        'curso-por-eixos' => 'ofertas',
        'plano-de-metas' => 'plano-de-metas',
        'pcas' => 'pcas',
        'visitas-tecnicas' => 'visitas-tecnicas',
        'horas-pedagogicas' => 'horas-pedagogicas',
        'acoes-extensivas' => 'acoes-extensivas',
        'eventos' => 'eventos',
        'resolucoes' => 'resolucoes',
        'termos-referencia' => 'termos-referencia',
        'jornadas-pedagogicas' => 'jornadas-pedagogicas',
        'unidades-oferta' => 'unidades',
        'regioes-administrativas' => 'unidades',
        'eixos' => 'eixos',
        'revisao-dados' => 'revisao-dados',
        'importacoes' => 'importacoes',
        'cadastros' => 'auditoria',
    ],

    'tabelas_externas' => [
        'cursos',
        'curso_por_eixos',
        'plano_de_metas',
        'pcas',
        'visita_tecnicas',
        'hora_pedagogicas',
        'acao_extensivas',
        'eventos',
        'resolucoes',
        'termos_referencia',
        'jornadas_pedagogicas',
        'unidades_oferta',
        'regioes_administrativas',
    ],
];
