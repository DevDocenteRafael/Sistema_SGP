<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnidadeOfertaRequest;
use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnidadeOfertaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar estruturas institucionais.')) {
            return $negado;
        }

        $query = UnidadeOferta::query()
            ->with('regiaoAdministrativa:id,nome,ativo')
            ->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('tipo', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%")
                    ->orWhere('endereco', 'like', "%{$busca}%")
                    ->orWhere('responsavel', 'like', "%{$busca}%")
                    ->orWhereHas('regiaoAdministrativa', fn ($ra) => $ra->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('regiao_administrativa_id')) {
            $query->where('regiao_administrativa_id', $request->regiao_administrativa_id);
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'tipos' => config('unidades_oferta.tipos'),
                'regioes' => RegiaoAdministrativa::query()
                    ->orderBy('nome')
                    ->get(['id', 'nome', 'ativo']),
            ],
        ]);
    }

    public function nomes(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar estruturas institucionais.')) {
            return $negado;
        }

        return response()->json([
            'data' => UnidadeOferta::nomesAtivos(),
        ]);
    }

    public function opcoes(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar estruturas institucionais.')) {
            return $negado;
        }

        $incluirInativas = filter_var($request->query('incluir_inativas', false), FILTER_VALIDATE_BOOLEAN);

        $regioes = RegiaoAdministrativa::query()
            ->when(! $incluirInativas, fn ($q) => $q->where('ativo', true))
            ->with(['unidadesOferta' => function ($q) use ($incluirInativas) {
                if (! $incluirInativas) {
                    $q->where('ativo', true);
                }
                $q->orderByRaw("CASE tipo WHEN 'faculdade' THEN 1 WHEN 'polo' THEN 2 WHEN 'unidade' THEN 3 WHEN 'cep' THEN 3 ELSE 4 END")
                    ->orderBy('nome');
            }])
            ->orderBy('nome')
            ->get();

        $tiposLabels = config('unidades_oferta.tipos', []);

        $data = $regioes->map(function (RegiaoAdministrativa $regiao) use ($tiposLabels) {
            $grupos = [];
            foreach (UnidadeOferta::TIPOS as $tipo) {
                $itens = $regiao->unidadesOferta
                    ->where('tipo', $tipo)
                    ->values()
                    ->map(fn (UnidadeOferta $u) => [
                        'id' => $u->id,
                        'nome' => $u->nome,
                        'tipo' => $u->tipo,
                        'ativo' => $u->ativo,
                    ])
                    ->all();

                if ($itens !== []) {
                    $grupos[] = [
                        'tipo' => $tipo,
                        'label' => $tiposLabels[$tipo] ?? strtoupper($tipo),
                        'unidades' => $itens,
                    ];
                }
            }

            return [
                'id' => $regiao->id,
                'nome' => $regiao->nome,
                'ativo' => $regiao->ativo,
                'grupos' => $grupos,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'tipos' => $tiposLabels,
            ],
        ]);
    }

    public function store(UnidadeOfertaRequest $request): JsonResponse
    {
        $dados = collect($request->validated())->except('localidade')->all();
        $dados['ativo'] = $dados['ativo'] ?? true;

        $unidade = UnidadeOferta::create($dados);

        return response()->json([
            'message' => 'Estrutura institucional cadastrada com sucesso.',
            'unidade_oferta' => $unidade->fresh()->load('regiaoAdministrativa:id,nome,ativo'),
        ], 201);
    }

    public function show(Request $request, UnidadeOferta $unidadeOferta): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta estrutura institucional.')) {
            return $negado;
        }

        return response()->json([
            'unidade_oferta' => $unidadeOferta->load('regiaoAdministrativa:id,nome,ativo'),
        ]);
    }

    public function update(UnidadeOfertaRequest $request, UnidadeOferta $unidadeOferta): JsonResponse
    {
        $unidadeOferta->fill(collect($request->validated())->except('localidade')->all());
        $unidadeOferta->save();

        return response()->json([
            'message' => 'Estrutura institucional atualizada com sucesso.',
            'unidade_oferta' => $unidadeOferta->fresh()->load('regiaoAdministrativa:id,nome,ativo'),
        ]);
    }
}
