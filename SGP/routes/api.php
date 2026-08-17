<?php

use App\Http\Controllers\Api\AcaoExtensivaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CadastroController;
use App\Http\Controllers\Api\CarometroController;
use App\Http\Controllers\Api\CpedEquipeController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\CursoPorEixoController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\FerramentaController;
use App\Http\Controllers\Api\FluxogramaController;
use App\Http\Controllers\Api\HoraPedagogicaController;
use App\Http\Controllers\Api\ImportacaoController;
use App\Http\Controllers\Api\JornadaPedagogicaController;
use App\Http\Controllers\Api\PortfolioCicloController;
use App\Http\Controllers\Api\KanbanController;
use App\Http\Controllers\Api\OrganogramaController;
use App\Http\Controllers\Api\PcaController;
use App\Http\Controllers\Api\PlanoDeMetaController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\ResolucaoController;
use App\Http\Controllers\Api\TermoReferenciaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VisitaTecnicaController;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'usuario.ativo'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Somente Administrador gerencia usuários (cadastra e define login/senha).
    Route::middleware('perfil:'.Usuario::PERFIL_ADMINISTRADOR)
        ->apiResource('usuarios', UsuarioController::class);

    // Auditoria (tabela cadastros) — somente leitura para Administrador.
    Route::get('cadastros', [CadastroController::class, 'index']);
    Route::get('cadastros/{cadastro}', [CadastroController::class, 'show']);
    Route::get('portfolio-ciclos', [PortfolioCicloController::class, 'index']);
    Route::post('portfolio-ciclos/gerar-proximo', [PortfolioCicloController::class, 'gerarProximo']);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('plano-de-metas', PlanoDeMetaController::class);
    Route::apiResource('resolucoes', ResolucaoController::class)
        ->parameters(['resolucoes' => 'resolucao']);
    Route::apiResource('termos-referencia', TermoReferenciaController::class)
        ->parameters(['termos-referencia' => 'termoReferencia']);
    Route::apiResource('pcas', PcaController::class);
    Route::apiResource('curso-por-eixos', CursoPorEixoController::class);
    Route::apiResource('horas-pedagogicas', HoraPedagogicaController::class)
        ->parameters(['horas-pedagogicas' => 'horaPedagogica']);
    Route::apiResource('visitas-tecnicas', VisitaTecnicaController::class)
        ->parameters(['visitas-tecnicas' => 'visitaTecnica']);
    Route::apiResource('acoes-extensivas', AcaoExtensivaController::class)
        ->parameters(['acoes-extensivas' => 'acaoExtensiva']);
    Route::apiResource('eventos', EventoController::class);
    Route::get('jornadas-pedagogicas/{jornadaPedagogica}/pdf', [JornadaPedagogicaController::class, 'pdf']);
    Route::apiResource('jornadas-pedagogicas', JornadaPedagogicaController::class)
        ->parameters(['jornadas-pedagogicas' => 'jornadaPedagogica']);
    Route::apiResource('cped-equipes', CpedEquipeController::class)
        ->parameters(['cped-equipes' => 'cpedEquipe']);
    Route::get('ferramentas', [FerramentaController::class, 'index']);
    Route::get('organograma', [OrganogramaController::class, 'index']);
    Route::get('carometro', [CarometroController::class, 'index']);

    Route::get('relatorios', [RelatorioController::class, 'index']);
    Route::get('relatorios/{tipo}/pdf', [RelatorioController::class, 'pdf']);

    Route::get('importacoes', [ImportacaoController::class, 'catalogo']);
    Route::post('importacoes/{modulo}/preview', [ImportacaoController::class, 'preview']);
    Route::post('importacoes/{modulo}/commit', [ImportacaoController::class, 'commit']);

    Route::prefix('kanban')->group(function () {
        Route::get('quadros', [KanbanController::class, 'indexQuadros']);
        Route::post('quadros', [KanbanController::class, 'storeQuadro']);
        Route::get('quadros/{kanbanQuadro}', [KanbanController::class, 'showQuadro']);
        Route::put('quadros/{kanbanQuadro}', [KanbanController::class, 'updateQuadro']);
        Route::delete('quadros/{kanbanQuadro}', [KanbanController::class, 'destroyQuadro']);

        Route::post('quadros/{kanbanQuadro}/colunas', [KanbanController::class, 'storeColuna']);
        Route::post('quadros/{kanbanQuadro}/cartoes', [KanbanController::class, 'store']);

        Route::put('colunas/{kanbanColuna}', [KanbanController::class, 'updateColuna']);
        Route::delete('colunas/{kanbanColuna}', [KanbanController::class, 'destroyColuna']);
        Route::put('cartoes/{kanbanCartao}', [KanbanController::class, 'update']);
        Route::delete('cartoes/{kanbanCartao}', [KanbanController::class, 'destroy']);
        Route::put('cartoes/{kanbanCartao}/mover', [KanbanController::class, 'mover']);
    });

    Route::prefix('fluxogramas')->group(function () {
        Route::get('/', [FluxogramaController::class, 'index']);
        Route::post('/', [FluxogramaController::class, 'store']);
        Route::get('{fluxograma}', [FluxogramaController::class, 'show']);
        Route::put('{fluxograma}', [FluxogramaController::class, 'update']);
        Route::delete('{fluxograma}', [FluxogramaController::class, 'destroy']);
    });
});
