<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CpedEquipeRequest;
use App\Models\CpedEquipe;
use App\Services\CpedEquipeFotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CpedEquipeController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly CpedEquipeFotoService $fotos,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar a equipe CPED.')) {
            return $negado;
        }

        $query = CpedEquipe::query()
            ->orderByRaw("CASE tipo
                WHEN 'ordenador' THEN 1
                WHEN 'assistente' THEN 2
                WHEN 'responsavel' THEN 3
                WHEN 'instrutor' THEN 4
                WHEN 'administrativo' THEN 5
                ELSE 6 END")
            ->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('cargo', 'like', "%{$busca}%")
                    ->orWhere('setor', 'like', "%{$busca}%")
                    ->orWhere('contato', 'like', "%{$busca}%")
                    ->orWhere('eixo_vinculado', 'like', "%{$busca}%")
                    ->orWhere('tipo', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('eixo')) {
            $query->where(function ($q) use ($request) {
                $q->where('eixo_vinculado', $request->eixo)
                    ->orWhere('setor', $request->eixo);
            });
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        $registros = $query->get();

        $ativos = CpedEquipe::query()->where('ativo', true)->count();
        $totalGeral = CpedEquipe::query()->count();
        $eixosComResponsavel = CpedEquipe::query()
            ->where('tipo', 'responsavel')
            ->whereNotNull('eixo_vinculado')
            ->where('eixo_vinculado', '!=', '')
            ->distinct()
            ->pluck('eixo_vinculado')
            ->all();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => $totalGeral,
                'contadores' => [
                    'colaboradores' => $ativos ?: $totalGeral,
                    'eixos' => count($eixosComResponsavel) ?: count(config('cped_equipes.eixos', [])),
                    'instrutores' => CpedEquipe::query()->where('tipo', 'instrutor')->count(),
                    'administrativos' => CpedEquipe::query()->where('tipo', 'administrativo')->count(),
                ],
                'tipos' => config('cped_equipes.tipos'),
                'tipos_filtro' => config('cped_equipes.tipos_filtro'),
                'setores_por_tipo' => config('cped_equipes.setores_por_tipo'),
                'tipos_labels' => config('cped_equipes.tipos_labels'),
                'tipos_grupos' => config('cped_equipes.tipos_grupos'),
                'cores_tipo' => config('cped_equipes.cores_tipo'),
                'eixos' => config('cped_equipes.eixos'),
                'cores_eixo' => config('cped_equipes.cores_eixo'),
                'setores' => config('cped_equipes.setores'),
            ],
        ]);
    }

    public function store(CpedEquipeRequest $request): JsonResponse
    {
        $payload = $this->normalizarPayload(
            $request->safe()->except(['foto', 'remover_foto'])
        );

        if ($request->hasFile('foto')) {
            $payload['foto'] = $this->fotos->salvar($request->file('foto'));
        }

        $registro = CpedEquipe::create($payload);

        return response()->json([
            'message' => 'Membro cadastrado com sucesso.',
            'cped_equipe' => $registro->fresh(),
        ], 201);
    }

    public function show(Request $request, CpedEquipe $cpedEquipe): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este membro.')) {
            return $negado;
        }

        return response()->json([
            'cped_equipe' => $cpedEquipe,
        ]);
    }

    public function update(CpedEquipeRequest $request, CpedEquipe $cpedEquipe): JsonResponse
    {
        $payload = $this->normalizarPayload(
            $request->safe()->except(['foto', 'remover_foto'])
        );

        $fotoAnterior = $cpedEquipe->caminhoFoto();

        if ($request->boolean('remover_foto')) {
            $payload['foto'] = null;
            $this->fotos->apagar($fotoAnterior);
        } elseif ($request->hasFile('foto')) {
            $payload['foto'] = $this->fotos->salvar($request->file('foto'));
            $this->fotos->apagar($fotoAnterior);
        }

        $cpedEquipe->update($payload);

        return response()->json([
            'message' => 'Membro atualizado com sucesso.',
            'cped_equipe' => $cpedEquipe->fresh(),
        ]);
    }

    public function destroy(Request $request, CpedEquipe $cpedEquipe): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir membros da equipe CPED.',
            ], 403);
        }

        $nome = $cpedEquipe->nome ?: 'Membro';
        $this->fotos->apagar($cpedEquipe->caminhoFoto());
        $cpedEquipe->delete();

        return response()->json([
            'message' => "Membro \"{$nome}\" excluído com sucesso.",
        ]);
    }

    private function normalizarPayload(array $payload): array
    {
        $tipo = $payload['tipo'] ?? null;

        if (! in_array($tipo, ['responsavel', 'instrutor'], true)) {
            $payload['eixo_vinculado'] = null;
        } elseif (empty($payload['eixo_vinculado']) && ! empty($payload['setor'])) {
            $eixos = config('cped_equipes.eixos', []);
            if (in_array($payload['setor'], $eixos, true)) {
                $payload['eixo_vinculado'] = $payload['setor'];
            }
        }

        if (empty($payload['iniciais']) && ! empty($payload['nome'])) {
            $payload['iniciais'] = $this->gerarIniciais($payload['nome']);
        } else {
            $payload['iniciais'] = strtoupper(trim((string) ($payload['iniciais'] ?? '')));
        }

        if (empty($payload['cor'])) {
            $payload['cor'] = $this->corPadrao($tipo, $payload['eixo_vinculado'] ?? null);
        }

        $payload['ativo'] = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : true;

        return $payload;
    }

    private function gerarIniciais(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];
        $partes = array_values(array_filter($partes));

        if (count($partes) === 0) {
            return 'CP';
        }

        if (count($partes) === 1) {
            return strtoupper(mb_substr($partes[0], 0, 2));
        }

        return strtoupper(mb_substr($partes[0], 0, 1).mb_substr($partes[count($partes) - 1], 0, 1));
    }

    private function corPadrao(?string $tipo, ?string $eixo): string
    {
        if ($eixo && isset(config('cped_equipes.cores_eixo')[$eixo])) {
            return config('cped_equipes.cores_eixo')[$eixo];
        }

        return config("cped_equipes.cores_tipo.{$tipo}", '#003F7D');
    }
}
