<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * API da lixeira: Root e Administrador listam e restauram (a tela fica na Auditoria).
 * Não há exclusão definitiva pela interface.
 */
class LixeiraController extends Controller
{
    private const LIMITE_POR_MODULO = 500;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeRestaurarRegistros()) {
            return response()->json(['message' => 'Você não tem permissão para consultar registros excluídos.'], 403);
        }

        $modulos = $this->modulos();
        $filtroModulo = (string) $request->input('modulo', '');
        $busca = mb_strtolower(trim((string) $request->input('busca', '')));

        $itens = collect();
        foreach ($modulos as $chave => $config) {
            if ($filtroModulo !== '' && $filtroModulo !== $chave) {
                continue;
            }

            /** @var class-string<Model> $model */
            $model = $config['model'];
            $registros = $model::onlyTrashed()
                ->orderByDesc('deleted_at')
                ->limit(self::LIMITE_POR_MODULO)
                ->get();

            foreach ($registros as $registro) {
                $itens->push($this->serializar($chave, $config, $registro));
            }
        }

        if ($busca !== '') {
            $itens = $itens->filter(fn (array $item) => str_contains(
                mb_strtolower($item['titulo'].' '.$item['modulo_label'].' '.($item['excluido_por'] ?? '').' #'.$item['id']),
                $busca,
            ));
        }

        $itens = $itens->sortByDesc('excluido_em')->values();
        $nomes = Usuario::query()->whereIn('id', $itens->pluck('excluido_por_id')->filter()->unique())->pluck('nome', 'id');
        $itens = $itens->map(function (array $item) use ($nomes) {
            $item['excluido_por'] = $item['excluido_por_id'] ? ($nomes[$item['excluido_por_id']] ?? 'Usuário #'.$item['excluido_por_id']) : null;

            return $item;
        });

        $porPagina = min(max((int) $request->input('per_page', 20), 1), 100);
        $pagina = max((int) $request->input('page', 1), 1);
        $total = $itens->count();

        return response()->json([
            'data' => $itens->slice(($pagina - 1) * $porPagina, $porPagina)->values(),
            'meta' => [
                'total' => $total,
                'per_page' => $porPagina,
                'current_page' => $pagina,
                'last_page' => max(1, (int) ceil($total / $porPagina)),
                'modulos' => collect($modulos)->map(fn (array $c, string $k) => ['value' => $k, 'label' => $c['label']])->values(),
            ],
        ]);
    }

    public function restaurar(Request $request, string $modulo, int $id): JsonResponse
    {
        if (! $request->user()?->podeRestaurarRegistros()) {
            return response()->json(['message' => 'Você não tem permissão para restaurar registros.'], 403);
        }

        $config = $this->modulos()[$modulo] ?? null;
        if (! $config) {
            return response()->json(['message' => 'Módulo não encontrado.'], 404);
        }

        /** @var class-string<Model> $model */
        $model = $config['model'];
        $registro = $model::onlyTrashed()->find($id);
        if (! $registro) {
            return response()->json(['message' => 'Registro excluído não encontrado (talvez já tenha sido restaurado).'], 404);
        }

        if ($bloqueio = $this->paiExcluido($registro)) {
            return response()->json(['message' => $bloqueio], 422);
        }

        DB::transaction(function () use ($registro) {
            $this->reposicionar($registro);
            $registro->restore();
        });

        return response()->json([
            'message' => '"'.$this->titulo($config, $registro).'" foi restaurado.',
            'item' => $this->serializar($modulo, $config, $registro->fresh()),
        ]);
    }

    /**
     * @return array<string, array{label: string, model: class-string<Model>, titulo: string, rota: string}>
     */
    private function modulos(): array
    {
        return config('exclusao.modulos', []);
    }

    /**
     * Restaurar um filho com o pai na lixeira deixaria o registro invisível.
     */
    private function paiExcluido(Model $registro): ?string
    {
        $relacoes = [
            'ciclo' => 'o ciclo de gestão',
            'quadro' => 'o quadro do Kanban',
            'coluna' => 'a coluna do Kanban',
        ];

        foreach ($relacoes as $relacao => $rotulo) {
            if (! method_exists($registro, $relacao)) {
                continue;
            }
            $rel = $registro->{$relacao}();
            if (! $rel instanceof BelongsTo) {
                continue;
            }
            $relacionado = $rel->getRelated();
            if (! in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($relacionado), true)) {
                continue;
            }
            $chave = $registro->getAttribute($rel->getForeignKeyName());
            if ($chave && $relacionado::onlyTrashed()->whereKey($chave)->exists()) {
                return 'Restaure antes '.$rotulo.' deste registro (ele também está entre os excluídos).';
            }
        }

        return null;
    }

    /**
     * Colunas e cartões do Kanban voltam para o fim da lista (as posições foram reorganizadas).
     */
    private function reposicionar(Model $registro): void
    {
        if ($registro instanceof KanbanColuna) {
            $registro->position = (int) KanbanColuna::query()->where('kanban_quadro_id', $registro->kanban_quadro_id)->max('position') + 1;
        } elseif ($registro instanceof KanbanCartao) {
            $registro->position = (int) KanbanCartao::query()->where('kanban_coluna_id', $registro->kanban_coluna_id)->max('position') + 1;
        }
    }

    /**
     * @param  array{label: string, titulo: string, rota: string}  $config
     */
    private function titulo(array $config, Model $registro): string
    {
        $valor = $registro->getAttribute($config['titulo']);

        return is_scalar($valor) && trim((string) $valor) !== '' ? trim((string) $valor) : $config['label'].' #'.$registro->getKey();
    }

    /**
     * @param  array{label: string, titulo: string, rota: string}  $config
     * @return array<string, mixed>
     */
    private function serializar(string $modulo, array $config, Model $registro): array
    {
        return [
            'modulo' => $modulo,
            'modulo_label' => $config['label'],
            'id' => $registro->getKey(),
            'titulo' => $this->titulo($config, $registro),
            'rota' => $config['rota'],
            'excluido_em' => $registro->getAttribute('deleted_at')?->toISOString(),
            'excluido_por_id' => $registro->getAttribute('excluido_por'),
            'excluido_por' => null,
        ];
    }
}
