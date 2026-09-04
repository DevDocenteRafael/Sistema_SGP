<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegiaoAdministrativaRequest;
use App\Models\RegiaoAdministrativa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegiaoAdministrativaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar regiões administrativas.')) {
            return $negado;
        }

        $query = RegiaoAdministrativa::query()
            ->withCount([
                'unidadesOferta as unidades_total',
                'unidadesOferta as unidades_ativas' => fn ($q) => $q->where('ativo', true),
            ])
            ->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where('nome', 'like', "%{$busca}%");
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
            ],
        ]);
    }

    public function store(RegiaoAdministrativaRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $dados['ativo'] = $dados['ativo'] ?? true;

        $regiao = RegiaoAdministrativa::create($dados);

        return response()->json([
            'message' => 'Região administrativa cadastrada com sucesso.',
            'regiao_administrativa' => $regiao->fresh()->loadCount([
                'unidadesOferta as unidades_total',
                'unidadesOferta as unidades_ativas' => fn ($q) => $q->where('ativo', true),
            ]),
        ], 201);
    }

    public function show(Request $request, RegiaoAdministrativa $regiaoAdministrativa): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta região administrativa.')) {
            return $negado;
        }

        $regiaoAdministrativa->loadCount([
            'unidadesOferta as unidades_total',
            'unidadesOferta as unidades_ativas' => fn ($q) => $q->where('ativo', true),
        ]);

        return response()->json([
            'regiao_administrativa' => $regiaoAdministrativa,
        ]);
    }

    public function update(RegiaoAdministrativaRequest $request, RegiaoAdministrativa $regiaoAdministrativa): JsonResponse
    {
        $regiaoAdministrativa->fill($request->validated());
        $regiaoAdministrativa->save();

        return response()->json([
            'message' => 'Região administrativa atualizada com sucesso.',
            'regiao_administrativa' => $regiaoAdministrativa->fresh()->loadCount([
                'unidadesOferta as unidades_total',
                'unidadesOferta as unidades_ativas' => fn ($q) => $q->where('ativo', true),
            ]),
        ]);
    }
}
