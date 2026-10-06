<?php

namespace App\Http\Controllers\Api;

use App\Models\UnidadeOferta;
use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Concerns\PaginatesIndex;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitaTecnicaRequest;
use App\Models\VisitaTecnica;
use App\Services\SvtIntegracaoService;
use App\Support\CatalogoOficial;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaTecnicaController extends Controller
{
    use AutorizaConsulta, PaginatesIndex;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar visitas técnicas.')) {
            return $negado;
        }

        $query = VisitaTecnica::query()
            ->orderByDesc('data_solicitacao')
            ->orderByDesc('id');

        if ($request->filled('id')) {
            $query->where('id', (int) $request->id);
        } else {
            \App\Models\Ciclo::aplicarFiltroNaConsulta($query, $request->input('ciclo_id'));
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('unidade', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('processo_sei', 'like', "%{$busca}%")
                    ->orWhere('responsavel', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('relatorio', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->whereYear('data_solicitacao', $request->ano);
        }

        if ($request->filled('unidade')) {
            $query->where('unidade', $request->unidade);
        }

        if ($request->filled('eixo')) {
            CatalogoOficial::aplicarFiltroEixo($query, $request->eixo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('prazo')) {
            $this->aplicarFiltroPrazo($query, $request->prazo);
        }

        if ($request->filled('origem')) {
            $request->origem === 'svt'
                ? $query->where('source_system', SvtIntegracaoService::sistema())
                : $query->where(fn ($q) => $q->whereNull('source_system')->orWhere('source_system', '!=', SvtIntegracaoService::sistema()));
        }

        $paginator = $this->paginar($query, $request);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (VisitaTecnica $v) => $this->serializar($v))->values(),
            'meta' => array_merge($this->metaPaginacao($paginator), [
                'origem' => app(SvtIntegracaoService::class)->situacao(),
                'etapas_svt' => config('svt.etapas', []),
                'total_geral' => VisitaTecnica::query()->count(),
                'eixos' => CatalogoOficial::eixos(),
                'status' => config('visitas_tecnicas.status'),
                'anos' => config('visitas_tecnicas.anos'),
                'unidades' => UnidadeOferta::nomesAtivos(),
                'prazos' => config('visitas_tecnicas.prazos'),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(VisitaTecnica $visita): array
    {
        $doSvt = SvtIntegracaoService::ehDoSvt($visita);

        return array_merge($visita->toArray(), [
            'origem_svt' => $doSvt,
            'editavel_no_siped' => ! $doSvt && ! SvtIntegracaoService::modoSvt(),
            'url_svt' => $doSvt ? SvtIntegracaoService::urlVisita($visita->external_id) : null,
        ]);
    }

    private function bloqueioSvt(?VisitaTecnica $visita = null): ?JsonResponse
    {
        if ($visita && SvtIntegracaoService::ehDoSvt($visita)) {
            return response()->json([
                'message' => 'Esta visita vem do SVT (Sistema de Visitas Técnicas) e só pode ser alterada lá.',
            ], 409);
        }

        if (SvtIntegracaoService::modoSvt()) {
            return response()->json([
                'message' => 'As visitas técnicas são cadastradas no SVT. No SIPED elas ficam disponíveis para consulta.',
            ], 409);
        }

        return null;
    }

    public function store(VisitaTecnicaRequest $request): JsonResponse
    {
        if ($bloqueio = $this->bloqueioSvt()) {
            return $bloqueio;
        }

        $registro = VisitaTecnica::create($request->validated());

        return response()->json([
            'message' => 'Visita técnica cadastrada com sucesso.',
            'visitaTecnica' => $registro,
        ], 201);
    }

    public function show(Request $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta visita técnica.')) {
            return $negado;
        }

        return response()->json([
            'visitaTecnica' => $this->serializar($visitaTecnica),
        ]);
    }

    public function update(VisitaTecnicaRequest $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        if ($bloqueio = $this->bloqueioSvt($visitaTecnica)) {
            return $bloqueio;
        }

        $visitaTecnica->update($request->validated());

        return response()->json([
            'message' => 'Visita técnica atualizada com sucesso.',
            'visitaTecnica' => $visitaTecnica->fresh(),
        ]);
    }

    public function destroy(Request $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir visitas técnicas.',
            ], 403);
        }

        if ($bloqueio = $this->bloqueioSvt($visitaTecnica)) {
            return $bloqueio;
        }

        $sei = $visitaTecnica->processo_sei;
        $visitaTecnica->delete();

        return response()->json([
            'message' => "Visita técnica \"{$sei}\" excluída com sucesso.",
        ]);
    }

    private function aplicarFiltroPrazo($query, string $prazo): void
    {
        $hoje = Carbon::today()->toDateString();

        if ($prazo === 'dentro') {
            $query->whereDate('prazo_limite', '>=', $hoje)
                ->whereNotIn('status', ['Atrasada', 'Cancelada']);

            return;
        }

        if ($prazo === 'fora') {
            $query->where(function ($q) use ($hoje) {
                $q->whereDate('prazo_limite', '<', $hoje)
                    ->orWhere('status', 'Atrasada');
            });
        }
    }
}
