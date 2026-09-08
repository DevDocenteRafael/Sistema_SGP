<?php

namespace App\Services;

use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\KanbanQuadro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class KanbanService
{
    public function listarQuadros(): array
    {
        return KanbanQuadro::query()
            ->where('ativo', true)
            ->withCount(['colunas', 'cartoes'])
            ->orderBy('nome')
            ->get()
            ->map(fn (KanbanQuadro $quadro) => $this->formatarQuadroResumo($quadro))
            ->values()
            ->all();
    }

    public function obterQuadroPorSlug(string $slug): KanbanQuadro
    {
        return KanbanQuadro::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->with([
                'colunas' => fn ($q) => $q->orderBy('position')
                    ->with(['cartoes' => fn ($q) => $q->orderBy('position')]),
            ])
            ->firstOrFail();
    }

    public function criarQuadro(string $nome): KanbanQuadro
    {
        return DB::transaction(function () use ($nome) {
            $nome = trim($nome);
            $slug = $this->gerarSlugUnico($nome);

            $quadro = KanbanQuadro::query()->create([
                'nome' => $nome,
                'slug' => $slug,
                'ativo' => true,
            ]);

            foreach (config('kanban.colunas_padrao', []) as $coluna) {
                KanbanColuna::query()->create([
                    'kanban_quadro_id' => $quadro->id,
                    'titulo' => $coluna['titulo'],
                    'position' => $coluna['position'],
                    'cor' => $coluna['cor'] ?? null,
                ]);
            }

            return $quadro->fresh()->loadCount(['colunas', 'cartoes']);
        });
    }

    public function atualizarQuadro(KanbanQuadro $quadro, string $nome): KanbanQuadro
    {
        $nome = trim($nome);
        $quadro->update(['nome' => $nome]);

        return $quadro->fresh()->loadCount(['colunas', 'cartoes']);
    }

    public function excluirQuadro(KanbanQuadro $quadro): void
    {
        $quadro->delete();
    }

    public function formatarQuadroResumo(KanbanQuadro $quadro): array
    {
        return [
            'id' => $quadro->id,
            'nome' => $quadro->nome,
            'slug' => $quadro->slug,
            'total_colunas' => (int) ($quadro->colunas_count ?? $quadro->colunas()->count()),
            'total_cartoes' => (int) ($quadro->cartoes_count ?? $quadro->cartoes()->count()),
            'updated_at' => $quadro->updated_at?->toISOString(),
        ];
    }

    public function formatarQuadro(KanbanQuadro $quadro): array
    {
        $colunas = $quadro->colunas->map(function (KanbanColuna $coluna) {
            return [
                'id' => $coluna->id,
                'titulo' => $coluna->titulo,
                'position' => $coluna->position,
                'cor' => $coluna->cor,
                'cartoes' => $coluna->cartoes->map(fn (KanbanCartao $cartao) => $this->formatarCartao($cartao))->values()->all(),
            ];
        })->values()->all();

        $totalCartoes = collect($colunas)->sum(fn (array $coluna) => count($coluna['cartoes']));

        return [
            'quadro' => [
                'id' => $quadro->id,
                'nome' => $quadro->nome,
                'slug' => $quadro->slug,
            ],
            'colunas' => $colunas,
            'total_cartoes' => $totalCartoes,
        ];
    }

    public function formatarCartao(KanbanCartao $cartao): array
    {
        return [
            'id' => $cartao->id,
            'kanban_coluna_id' => $cartao->kanban_coluna_id,
            'titulo' => $cartao->titulo,
            'descricao' => $cartao->descricao,
            'position' => $cartao->position,
            'criado_por' => $cartao->criado_por,
            'created_at' => $cartao->created_at?->toISOString(),
            'updated_at' => $cartao->updated_at?->toISOString(),
        ];
    }

    public function formatarColuna(KanbanColuna $coluna, bool $comCartoes = false): array
    {
        $data = [
            'id' => $coluna->id,
            'titulo' => $coluna->titulo,
            'position' => $coluna->position,
            'cor' => $coluna->cor,
        ];

        if ($comCartoes) {
            $coluna->loadMissing(['cartoes' => fn ($q) => $q->orderBy('position')]);
            $data['cartoes'] = $coluna->cartoes
                ->map(fn (KanbanCartao $cartao) => $this->formatarCartao($cartao))
                ->values()
                ->all();
        }

        return $data;
    }

    public function findOrCreateColuna(KanbanQuadro $quadro, string $titulo): array
    {
        $titulo = trim($titulo);

        $existente = $this->buscarColunaPorTitulo($quadro->id, $titulo);

        if ($existente) {
            return [
                'coluna' => $existente,
                'criada' => false,
            ];
        }

        return [
            'coluna' => $this->criarColunaNoQuadro($quadro, $titulo),
            'criada' => true,
        ];
    }

    public function criarColuna(KanbanQuadro $quadro, string $titulo): KanbanColuna
    {
        return DB::transaction(function () use ($quadro, $titulo) {
            $quadro = KanbanQuadro::query()->lockForUpdate()->findOrFail($quadro->id);
            $titulo = trim($titulo);

            if ($this->buscarColunaPorTitulo($quadro->id, $titulo)) {
                throw new InvalidArgumentException('Já existe uma coluna com este nome neste quadro.');
            }

            return $this->criarColunaNoQuadro($quadro, $titulo);
        });
    }

    public function atualizarColuna(KanbanColuna $coluna, string $titulo): KanbanColuna
    {
        return DB::transaction(function () use ($coluna, $titulo) {
            $coluna = KanbanColuna::query()->lockForUpdate()->findOrFail($coluna->id);
            $titulo = trim($titulo);

            $duplicada = $this->buscarColunaPorTitulo(
                $coluna->kanban_quadro_id,
                $titulo,
                $coluna->id
            );

            if ($duplicada) {
                throw new InvalidArgumentException('Já existe uma coluna com este nome neste quadro.');
            }

            $coluna->update(['titulo' => $titulo]);

            return $coluna->fresh();
        });
    }

    public function excluirColuna(KanbanColuna $coluna): void
    {
        DB::transaction(function () use ($coluna) {
            $coluna = KanbanColuna::query()->lockForUpdate()->findOrFail($coluna->id);
            $quadroId = $coluna->kanban_quadro_id;
            $posicao = $coluna->position;

            $coluna->delete();

            KanbanColuna::query()
                ->where('kanban_quadro_id', $quadroId)
                ->where('position', '>', $posicao)
                ->decrement('position');
        });
    }

    /**
     * @return array{cartao: KanbanCartao, coluna: KanbanColuna, coluna_criada: bool}
     */
    public function criarCartao(KanbanQuadro $quadro, array $payload, ?int $usuarioId = null): array
    {
        return DB::transaction(function () use ($quadro, $payload, $usuarioId) {
            $quadro = KanbanQuadro::query()->lockForUpdate()->findOrFail($quadro->id);

            $resultadoColuna = $this->findOrCreateColuna($quadro, $payload['coluna_titulo']);
            $coluna = $resultadoColuna['coluna'];
            $colunaId = $coluna->id;

            $proximaPosicao = (int) KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaId)
                ->max('position');

            $position = KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaId)
                ->exists()
                ? $proximaPosicao + 1
                : 0;

            $cartao = KanbanCartao::query()->create([
                'kanban_coluna_id' => $colunaId,
                'titulo' => $payload['titulo'],
                'descricao' => $payload['descricao'] ?? null,
                'position' => $position,
                'criado_por' => $usuarioId,
            ]);

            return [
                'cartao' => $cartao,
                'coluna' => $coluna->fresh(),
                'coluna_criada' => $resultadoColuna['criada'],
            ];
        });
    }

    public function atualizarCartao(KanbanCartao $cartao, array $payload): KanbanCartao
    {
        $cartao->update([
            'titulo' => $payload['titulo'],
            'descricao' => $payload['descricao'] ?? null,
        ]);

        return $cartao->fresh();
    }

    public function excluirCartao(KanbanCartao $cartao): void
    {
        DB::transaction(function () use ($cartao) {
            $colunaId = $cartao->kanban_coluna_id;
            $posicao = $cartao->position;

            $cartao->delete();

            KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaId)
                ->where('position', '>', $posicao)
                ->decrement('position');
        });
    }

    public function moverCartao(KanbanCartao $cartao, int $colunaDestinoId, int $novaPosicao): KanbanCartao
    {
        return DB::transaction(function () use ($cartao, $colunaDestinoId, $novaPosicao) {
            $cartao = KanbanCartao::query()->lockForUpdate()->findOrFail($cartao->id);

            $colunaDestino = KanbanColuna::query()->findOrFail($colunaDestinoId);
            $colunaOrigemId = $cartao->kanban_coluna_id;
            $posicaoOrigem = $cartao->position;

            $colunaOrigem = KanbanColuna::query()->findOrFail($colunaOrigemId);

            if ($colunaOrigem->kanban_quadro_id !== $colunaDestino->kanban_quadro_id) {
                throw new InvalidArgumentException('Não é possível mover cartões entre quadros diferentes.');
            }

            $totalDestino = KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaDestinoId)
                ->when($colunaOrigemId === $colunaDestinoId, fn ($q) => $q->where('id', '!=', $cartao->id))
                ->count();

            if ($novaPosicao < 0 || $novaPosicao > $totalDestino) {
                throw new InvalidArgumentException('Posição inválida para o cartão.');
            }

            if ($colunaOrigemId === $colunaDestinoId && $posicaoOrigem === $novaPosicao) {
                return $cartao;
            }

            if ($colunaOrigemId === $colunaDestinoId) {
                if ($novaPosicao > $posicaoOrigem) {
                    KanbanCartao::query()
                        ->where('kanban_coluna_id', $colunaOrigemId)
                        ->where('id', '!=', $cartao->id)
                        ->where('position', '>', $posicaoOrigem)
                        ->where('position', '<=', $novaPosicao)
                        ->decrement('position');
                } else {
                    KanbanCartao::query()
                        ->where('kanban_coluna_id', $colunaOrigemId)
                        ->where('id', '!=', $cartao->id)
                        ->where('position', '>=', $novaPosicao)
                        ->where('position', '<', $posicaoOrigem)
                        ->increment('position');
                }

                $cartao->update(['position' => $novaPosicao]);

                return $cartao->fresh();
            }

            KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaOrigemId)
                ->where('position', '>', $posicaoOrigem)
                ->decrement('position');

            KanbanCartao::query()
                ->where('kanban_coluna_id', $colunaDestino->id)
                ->where('position', '>=', $novaPosicao)
                ->increment('position');

            $cartao->update([
                'kanban_coluna_id' => $colunaDestino->id,
                'position' => $novaPosicao,
            ]);

            return $cartao->fresh();
        });
    }

    private function gerarSlugUnico(string $nome): string
    {
        $base = Str::slug($nome) ?: 'quadro';
        $slug = $base;
        $i = 2;

        while (KanbanQuadro::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function buscarColunaPorTitulo(int $quadroId, string $titulo, ?int $excetoId = null): ?KanbanColuna
    {
        return KanbanColuna::query()
            ->where('kanban_quadro_id', $quadroId)
            ->when($excetoId, fn ($q) => $q->where('id', '!=', $excetoId))
            ->get()
            ->first(fn (KanbanColuna $coluna) => mb_strtolower($coluna->titulo) === mb_strtolower($titulo));
    }

    private function criarColunaNoQuadro(KanbanQuadro $quadro, string $titulo): KanbanColuna
    {
        $proximaPosicao = (int) KanbanColuna::query()
            ->where('kanban_quadro_id', $quadro->id)
            ->max('position');

        $position = KanbanColuna::query()
            ->where('kanban_quadro_id', $quadro->id)
            ->exists()
            ? $proximaPosicao + 1
            : 0;

        $cores = [
            '#64748B',
            '#003F7D',
            '#16A34A',
            '#F57C00',
            '#7C3AED',
            '#0D9488',
            '#DB2777',
        ];

        return KanbanColuna::query()->create([
            'kanban_quadro_id' => $quadro->id,
            'titulo' => $titulo,
            'position' => $position,
            'cor' => $cores[$position % count($cores)],
        ]);
    }
}
