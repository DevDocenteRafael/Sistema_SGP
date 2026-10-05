<?php

/**
 * Exclusão de registros de negócio (SPEC 03).
 *
 * Por padrão tudo é exclusão lógica (lixeira) e pode ser restaurado por Root/Administrador.
 * A exclusão definitiva só existe como procedimento técnico excepcional, fora da interface:
 * ligar SIPED_PERMITIR_EXCLUSAO_DEFINITIVA=true temporariamente no servidor.
 */
return [
    'permitir_definitiva' => (bool) env('SIPED_PERMITIR_EXCLUSAO_DEFINITIVA', false),

    /*
    | Módulos com lixeira: chave => [rótulo, model, campo de título, rota no front]
    */
    'modulos' => [
        'cursos' => ['label' => 'Cursos', 'model' => App\Models\Curso::class, 'titulo' => 'titulo', 'rota' => '/app/cursos'],
        'eixos' => ['label' => 'Ofertas por eixo', 'model' => App\Models\CursoPorEixo::class, 'titulo' => 'curso', 'rota' => '/app/eixos'],
        'plano-de-metas' => ['label' => 'Plano de Metas', 'model' => App\Models\PlanoDeMeta::class, 'titulo' => 'curso', 'rota' => '/app/plano-de-metas'],
        'pcas' => ['label' => 'PCA', 'model' => App\Models\Pca::class, 'titulo' => 'titulo', 'rota' => '/app/pca'],
        'resolucoes' => ['label' => 'Resoluções', 'model' => App\Models\Resolucao::class, 'titulo' => 'numero', 'rota' => '/app/controle-de-resolucoes'],
        'termos-referencia' => ['label' => 'Termos de Referência', 'model' => App\Models\TermoReferencia::class, 'titulo' => 'nome', 'rota' => '/app/termos-de-referencia'],
        'visitas-tecnicas' => ['label' => 'Visitas Técnicas', 'model' => App\Models\VisitaTecnica::class, 'titulo' => 'processo_sei', 'rota' => '/app/visitas-tecnicas'],
        'horas-pedagogicas' => ['label' => 'Horas Pedagógicas', 'model' => App\Models\HoraPedagogica::class, 'titulo' => 'pessoa', 'rota' => '/app/horas-pedagogicas'],
        'acoes-extensivas' => ['label' => 'Ações Extensivas', 'model' => App\Models\AcaoExtensiva::class, 'titulo' => 'assunto', 'rota' => '/app/acoes-extensivas'],
        'eventos' => ['label' => 'Eventos', 'model' => App\Models\Evento::class, 'titulo' => 'nome', 'rota' => '/app/eventos'],
        'jornadas-pedagogicas' => ['label' => 'Jornada Pedagógica', 'model' => App\Models\JornadaPedagogica::class, 'titulo' => 'titulo', 'rota' => '/app/jornada-pedagogica'],
        'ciclos' => ['label' => 'Ciclos de gestão', 'model' => App\Models\Ciclo::class, 'titulo' => 'nome', 'rota' => '/app/ciclos'],
        'cped' => ['label' => 'Equipe CPED', 'model' => App\Models\CpedEquipe::class, 'titulo' => 'nome', 'rota' => '/app/cped'],
        'kanban-quadros' => ['label' => 'Kanban — quadros', 'model' => App\Models\KanbanQuadro::class, 'titulo' => 'nome', 'rota' => '/app/ferramentas/kanban'],
        'kanban-colunas' => ['label' => 'Kanban — colunas', 'model' => App\Models\KanbanColuna::class, 'titulo' => 'titulo', 'rota' => '/app/ferramentas/kanban'],
        'kanban-cartoes' => ['label' => 'Kanban — cartões', 'model' => App\Models\KanbanCartao::class, 'titulo' => 'titulo', 'rota' => '/app/ferramentas/kanban'],
        'fluxogramas' => ['label' => 'Fluxogramas', 'model' => App\Models\Fluxograma::class, 'titulo' => 'titulo', 'rota' => '/app/ferramentas/fluxograma'],
        'documentos' => ['label' => 'Documentos PDF', 'model' => App\Models\Documento::class, 'titulo' => 'titulo', 'rota' => '/app/importacoes'],
    ],
];
