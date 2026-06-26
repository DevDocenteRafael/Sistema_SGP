<?php
use App\Http\Controllers\Api\AcaoExtensivaController;
use App\Http\Controllers\Api\CadastroController;
use App\Http\Controllers\Api\CpedEquipeController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\CursoPorEixoController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\HoraPedagogicaController;
use App\Http\Controllers\Api\PcaController;
use App\Http\Controllers\Api\PlanoDeMetaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VisitaTecnicaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas REST dos 11 módulos
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('cadastros', CadastroController::class);
Route::apiResource('cursos', CursoController::class);
Route::apiResource('plano-de-metas', PlanoDeMetaController::class);
Route::apiResource('pcas', PcaController::class);
Route::apiResource('curso-por-eixos', CursoPorEixoController::class);
Route::apiResource('horas-pedagogicas', HoraPedagogicaController::class);
Route::apiResource('visitas-tecnicas', VisitaTecnicaController::class);
Route::apiResource('acoes-extensivas', AcaoExtensivaController::class);
Route::apiResource('eventos', EventoController::class);
Route::apiResource('cped-equipes', CpedEquipeController::class);
