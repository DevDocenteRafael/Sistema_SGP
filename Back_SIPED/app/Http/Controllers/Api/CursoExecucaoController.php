<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CursoExecucaoRequest;
use App\Http\Requests\VincularCursoExecucaoRequest;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Models\UnidadeOferta;
use App\Services\CicloContextoService;
use App\Support\CatalogoOficial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoExecucaoController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar o acompanhamento do ciclo.')) {
            return $negado;
        }

        $query = CursoExecucao::query()->orderBy('curso')->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('curso', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%")
                    ->orWhere('instrutores', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ano', $request->ano);
        }

        Ciclo::aplicarFiltroNaConsulta($query, $request->input('ciclo_id'));

        if ($request->filled('eixo')) {
            CatalogoOficial::aplicarFiltroEixo($query, $request->eixo);
        }

        if ($request->filled('eixo_id')) {
            $query->where('eixo_id', (int) $request->eixo_id);
        }

        if ($request->filled('curso_id')) {
            $query->where('curso_id', (int) $request->curso_id);
        }

        if ($request->filled('unidade')) {
            $query->where('unidade', $request->unidade);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'eixos' => CatalogoOficial::eixos(),
                'segmentos' => CatalogoOficial::segmentos(),
                'programas' => CatalogoOficial::programas(),
                'status' => config('curso_por_eixos.status'),
                'anos' => config('curso_por_eixos.anos'),
                'unidades' => UnidadeOferta::nomesAtivos(),
            ],
        ]);
    }

    public function store(CursoExecucaoRequest $request): JsonResponse
    {
        $registro = CursoExecucao::create($this->payloadHerdado($request->validated(), $request));

        return response()->json([
            'message' => 'Acompanhamento do ciclo registrado com sucesso.',
            'acompanhamento' => $registro,
            'cursoExecucao' => $registro,
        ], 201);
    }

    public function show(Request $request, CursoExecucao $cursoExecucao): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este acompanhamento.')) {
            return $negado;
        }

        return response()->json([
            'acompanhamento' => $cursoExecucao,
            'cursoExecucao' => $cursoExecucao,
        ]);
    }

    public function update(CursoExecucaoRequest $request, CursoExecucao $cursoExecucao): JsonResponse
    {
        $payload = $this->payloadHerdado($request->validated(), $request, $cursoExecucao);
        $cursoExecucao->update($payload);

        $atualizado = $cursoExecucao->fresh();

        return response()->json([
            'message' => 'Acompanhamento do ciclo atualizado com sucesso.',
            'acompanhamento' => $atualizado,
            'cursoExecucao' => $atualizado,
        ]);
    }

    public function destroy(Request $request, CursoExecucao $cursoExecucao): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir este acompanhamento.',
            ], 403);
        }

        $titulo = $cursoExecucao->curso;
        $cursoExecucao->delete();

        return response()->json([
            'message' => "Acompanhamento \"{$titulo}\" excluído com sucesso.",
        ]);
    }

    public function vincular(VincularCursoExecucaoRequest $request, CursoExecucao $cursoExecucao): JsonResponse
    {
        $curso = Curso::query()->findOrFail((int) $request->validated('curso_id'));

        if ($cursoExecucao->ciclo_id && $curso->ciclo_id
            && (int) $cursoExecucao->ciclo_id !== (int) $curso->ciclo_id) {
            return response()->json([
                'message' => 'Não é possível vincular registros de ciclos de gestão diferentes.',
            ], 422);
        }

        $cursoExecucao->fill($this->camposDoCurso($curso, $cursoExecucao));
        $cursoExecucao->curso_id = $curso->id;
        if (! $cursoExecucao->ciclo_id && $curso->ciclo_id) {
            $cursoExecucao->ciclo_id = $curso->ciclo_id;
        }
        $cursoExecucao->save();

        $atualizado = $cursoExecucao->fresh();

        return response()->json([
            'message' => 'Registro vinculado ao curso oficial.',
            'acompanhamento' => $atualizado,
            'cursoExecucao' => $atualizado,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function payloadHerdado(array $payload, Request $request, ?CursoExecucao $existente = null): array
    {
        $cursoId = $payload['curso_id'] ?? $existente?->curso_id;
        if ($cursoId) {
            $curso = Curso::query()->findOrFail((int) $cursoId);
            if (! empty($payload['ciclo_id']) && $curso->ciclo_id
                && (int) $payload['ciclo_id'] !== (int) $curso->ciclo_id) {
                abort(response()->json([
                    'message' => 'Não é possível vincular registros de ciclos de gestão diferentes.',
                ], 422));
            }
            $payload = array_merge($this->camposDoCurso($curso, $existente), $payload);
            $payload['curso_id'] = $curso->id;
            if (empty($payload['curso'])) {
                $payload['curso'] = $curso->titulo;
            }
            if (empty($payload['ciclo_id']) && $curso->ciclo_id) {
                $payload['ciclo_id'] = $curso->ciclo_id;
            }
        }

        if (empty($payload['ciclo_id'])) {
            $payload['ciclo_id'] = $existente?->ciclo_id
                ?? app(CicloContextoService::class)->id($request);
        }

        if (empty($payload['status'])) {
            $payload['status'] = $existente?->status ?? 'Ativo';
        }

        if (empty($payload['ano'])) {
            $ciclo = $payload['ciclo_id']
                ? Ciclo::query()->find($payload['ciclo_id'])
                : app(CicloContextoService::class)->resolver($request);
            $payload['ano'] = $existente?->ano ?? ($ciclo?->anos()[0] ?? date('Y'));
        }

        $payload['is_novo'] = (bool) ($payload['is_novo'] ?? false);
        $payload['unidade'] = $payload['unidade'] ?? $existente?->unidade;

        return $payload;
    }

    /**
     * Eixo e segmento vêm do curso oficial; o título importado é preservado na edição.
     *
     * @return array<string, mixed>
     */
    private function camposDoCurso(Curso $curso, ?CursoExecucao $existente = null): array
    {
        return [
            'curso' => $existente?->curso ?: $curso->titulo,
            'eixo' => $curso->eixo,
            'eixo_id' => $curso->eixo_id,
            'segmento' => $curso->segmento,
            'segmento_id' => $curso->segmento_id,
            'programa' => $curso->programa,
        ];
    }
}
