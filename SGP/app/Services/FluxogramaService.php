<?php

namespace App\Services;

use App\Models\Fluxograma;
use Illuminate\Support\Str;

class FluxogramaService
{
    public function listar(): array
    {
        return Fluxograma::query()
            ->where('ativo', true)
            ->orderBy('titulo')
            ->get()
            ->map(fn (Fluxograma $fluxograma) => $this->formatarResumo($fluxograma))
            ->values()
            ->all();
    }

    public function obterPorSlug(string $slug): Fluxograma
    {
        return Fluxograma::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->firstOrFail();
    }

    public function criar(array $dados, ?int $criadoPor = null): Fluxograma
    {
        $titulo = trim($dados['titulo']);
        $tipo = $dados['tipo'] ?? Fluxograma::TIPO_LINEAR;

        return Fluxograma::query()->create([
            'titulo' => $titulo,
            'slug' => $this->gerarSlugUnico($titulo),
            'descricao' => isset($dados['descricao']) ? trim((string) $dados['descricao']) ?: null : null,
            'tipo' => $tipo,
            'diagrama' => $this->diagramaPadrao($tipo),
            'ativo' => true,
            'criado_por' => $criadoPor,
        ]);
    }

    public function atualizar(Fluxograma $fluxograma, array $dados): Fluxograma
    {
        $payload = [];

        if (array_key_exists('titulo', $dados)) {
            $payload['titulo'] = trim((string) $dados['titulo']);
        }

        if (array_key_exists('descricao', $dados)) {
            $payload['descricao'] = trim((string) ($dados['descricao'] ?? '')) ?: null;
        }

        if (array_key_exists('tipo', $dados)) {
            $payload['tipo'] = $dados['tipo'];
        }

        if (array_key_exists('diagrama', $dados) && is_array($dados['diagrama'])) {
            $payload['diagrama'] = $this->normalizarDiagrama($dados['diagrama']);
        }

        if ($payload !== []) {
            $fluxograma->update($payload);
        }

        return $fluxograma->fresh();
    }

    public function excluir(Fluxograma $fluxograma): void
    {
        $fluxograma->delete();
    }

    public function formatarResumo(Fluxograma $fluxograma): array
    {
        $diagrama = is_array($fluxograma->diagrama) ? $fluxograma->diagrama : [];
        $nodes = $diagrama['nodes'] ?? [];

        return [
            'id' => $fluxograma->id,
            'titulo' => $fluxograma->titulo,
            'slug' => $fluxograma->slug,
            'descricao' => $fluxograma->descricao,
            'tipo' => $fluxograma->tipo,
            'total_nos' => is_array($nodes) ? count($nodes) : 0,
            'updated_at' => $fluxograma->updated_at?->toISOString(),
        ];
    }

    public function formatarDetalhe(Fluxograma $fluxograma): array
    {
        return [
            'id' => $fluxograma->id,
            'titulo' => $fluxograma->titulo,
            'slug' => $fluxograma->slug,
            'descricao' => $fluxograma->descricao,
            'tipo' => $fluxograma->tipo,
            'diagrama' => $this->normalizarDiagrama(
                is_array($fluxograma->diagrama) ? $fluxograma->diagrama : []
            ),
            'updated_at' => $fluxograma->updated_at?->toISOString(),
        ];
    }

    public function diagramaPadrao(?string $tipo = null): array
    {
        if ($tipo === Fluxograma::TIPO_FUNCIONAL) {
            return $this->diagramaPadraoFuncional();
        }

        return $this->diagramaPadraoLinear();
    }

    private function diagramaPadraoLinear(): array
    {
        return [
            'raias' => [],
            'nodes' => [
                [
                    'id' => 'tpl-inicio',
                    'type' => 'inicio',
                    'position' => ['x' => 250, 'y' => 0],
                    'data' => [
                        'label' => 'Início',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => '',
                    ],
                ],
                [
                    'id' => 'tpl-processo',
                    'type' => 'processo',
                    'position' => ['x' => 235, 'y' => 120],
                    'data' => [
                        'label' => 'Processo',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => '',
                    ],
                ],
                [
                    'id' => 'tpl-fim',
                    'type' => 'fim',
                    'position' => ['x' => 250, 'y' => 250],
                    'data' => [
                        'label' => 'Fim',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => '',
                    ],
                ],
            ],
            'edges' => [
                [
                    'id' => 'tpl-e1',
                    'source' => 'tpl-inicio',
                    'target' => 'tpl-processo',
                    'sourceHandle' => 'out',
                    'targetHandle' => 'in',
                    'label' => '',
                    'type' => 'smoothstep',
                    'markerEnd' => ['type' => 'arrowclosed'],
                ],
                [
                    'id' => 'tpl-e2',
                    'source' => 'tpl-processo',
                    'target' => 'tpl-fim',
                    'sourceHandle' => 'out',
                    'targetHandle' => 'in',
                    'label' => '',
                    'type' => 'smoothstep',
                    'markerEnd' => ['type' => 'arrowclosed'],
                ],
            ],
            'viewport' => [
                'x' => 0,
                'y' => 0,
                'zoom' => 1,
            ],
        ];
    }

    private function diagramaPadraoFuncional(): array
    {
        $raias = [
            [
                'id' => 'raia-1',
                'nome' => 'Área / Setor 1',
                'ordem' => 0,
                'altura' => 220,
            ],
            [
                'id' => 'raia-2',
                'nome' => 'Área / Setor 2',
                'ordem' => 1,
                'altura' => 220,
            ],
        ];

        return [
            'raias' => $raias,
            'nodes' => [
                [
                    'id' => 'tpl-inicio',
                    'type' => 'inicio',
                    'position' => ['x' => 220, 'y' => 55],
                    'data' => [
                        'label' => 'Início',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => 'raia-1',
                    ],
                ],
                [
                    'id' => 'tpl-processo',
                    'type' => 'processo',
                    'position' => ['x' => 200, 'y' => 50],
                    'data' => [
                        'label' => 'Processo',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => 'raia-2',
                    ],
                ],
                [
                    'id' => 'tpl-fim',
                    'type' => 'fim',
                    'position' => ['x' => 480, 'y' => 55],
                    'data' => [
                        'label' => 'Fim',
                        'responsavel' => '',
                        'observacao' => '',
                        'raiaId' => 'raia-2',
                    ],
                ],
            ],
            'edges' => [
                [
                    'id' => 'tpl-e1',
                    'source' => 'tpl-inicio',
                    'target' => 'tpl-processo',
                    'sourceHandle' => 'bottom',
                    'targetHandle' => 'top',
                    'label' => '',
                    'type' => 'smoothstep',
                    'markerEnd' => ['type' => 'arrowclosed'],
                ],
                [
                    'id' => 'tpl-e2',
                    'source' => 'tpl-processo',
                    'target' => 'tpl-fim',
                    'sourceHandle' => 'bottom',
                    'targetHandle' => 'top',
                    'label' => '',
                    'type' => 'smoothstep',
                    'markerEnd' => ['type' => 'arrowclosed'],
                ],
            ],
            'viewport' => [
                'x' => 0,
                'y' => 0,
                'zoom' => 0.85,
            ],
        ];
    }

    private function viewportPadrao(): array
    {
        return [
            'x' => 0,
            'y' => 0,
            'zoom' => 1,
        ];
    }

    private function normalizarDiagrama(array $diagrama): array
    {
        $viewportPadrao = $this->viewportPadrao();
        $raias = [];

        if (is_array($diagrama['raias'] ?? null)) {
            foreach (array_values($diagrama['raias']) as $index => $raia) {
                if (! is_array($raia)) {
                    continue;
                }

                $raias[] = [
                    'id' => (string) ($raia['id'] ?? 'raia-'.($index + 1)),
                    'nome' => mb_substr(trim((string) ($raia['nome'] ?? 'Raia '.($index + 1))), 0, 80),
                    'ordem' => (int) ($raia['ordem'] ?? $index),
                    'altura' => max(140, (int) ($raia['altura'] ?? 200)),
                ];
            }
        }

        return [
            'raias' => $raias,
            'nodes' => array_values(is_array($diagrama['nodes'] ?? null) ? $diagrama['nodes'] : []),
            'edges' => array_values(is_array($diagrama['edges'] ?? null) ? $diagrama['edges'] : []),
            'viewport' => [
                'x' => (float) ($diagrama['viewport']['x'] ?? $viewportPadrao['x']),
                'y' => (float) ($diagrama['viewport']['y'] ?? $viewportPadrao['y']),
                'zoom' => (float) ($diagrama['viewport']['zoom'] ?? $viewportPadrao['zoom']),
            ],
        ];
    }

    private function gerarSlugUnico(string $titulo): string
    {
        $base = Str::slug($titulo) ?: 'fluxograma';
        $slug = $base;
        $i = 2;

        while (Fluxograma::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
