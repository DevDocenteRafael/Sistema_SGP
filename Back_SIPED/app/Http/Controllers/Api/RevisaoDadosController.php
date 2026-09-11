<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassificarCursoRequest;
use App\Http\Requests\VincularCursoExecucaoRequest;
use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Services\RevisaoDadosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisaoDadosController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly RevisaoDadosService $revisao,
        private readonly CursoExecucaoController $execucoes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar a revisão de dados.')) {
            return $negado;
        }

        $tipo = $request->input('tipo', 'sem_eixo');
        if (! in_array($tipo, ['sem_eixo', 'sem_vinculo'], true)) {
            return response()->json([
                'message' => 'Informe o tipo sem_eixo ou sem_vinculo.',
            ], 422);
        }

        return response()->json($this->revisao->listar(
            $tipo,
            $request->input('ciclo_id'),
            $request->input('busca'),
            (int) $request->input('per_page', 25),
        ));
    }

    public function classificar(ClassificarCursoRequest $request, Curso $curso): JsonResponse
    {
        $curso = $this->revisao->classificar(
            $curso,
            (string) $request->validated('eixo'),
            $request->validated('segmento'),
        );

        return response()->json([
            'message' => 'Curso classificado no eixo oficial.',
            'curso' => $curso,
        ]);
    }

    public function vincular(VincularCursoExecucaoRequest $request, CursoExecucao $cursoExecucao): JsonResponse
    {
        return $this->execucoes->vincular($request, $cursoExecucao);
    }
}
