<?php

namespace Database\Seeders;

use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\KanbanQuadro;
use Illuminate\Database\Seeder;

class KanbanSeeder extends Seeder
{
    public function run(): void
    {
        $quadro = KanbanQuadro::query()->updateOrCreate(
            ['slug' => config('kanban.quadro_padrao', 'cped')],
            [
                'nome' => 'Atividades CPED',
                'ativo' => true,
            ]
        );

        $colunasPorTitulo = [];

        foreach (config('kanban.colunas_padrao', []) as $colunaData) {
            $coluna = KanbanColuna::query()->updateOrCreate(
                [
                    'kanban_quadro_id' => $quadro->id,
                    'titulo' => $colunaData['titulo'],
                ],
                [
                    'position' => $colunaData['position'],
                    'cor' => $colunaData['cor'] ?? null,
                ]
            );

            $colunasPorTitulo[$coluna->titulo] = $coluna;
        }

        if (KanbanCartao::query()
            ->whereIn('kanban_coluna_id', collect($colunasPorTitulo)->pluck('id'))
            ->exists()) {
            return;
        }

        $exemplos = [
            'A Fazer' => [
                [
                    'titulo' => 'Revisar calendário pedagógico',
                    'descricao' => 'Conferir datas de aulas e feriados do semestre.',
                    'position' => 0,
                ],
                [
                    'titulo' => 'Atualizar organograma CPED',
                    'descricao' => 'Incluir novos membros da equipe no carômetro.',
                    'position' => 1,
                ],
            ],
            'Em Progresso' => [
                [
                    'titulo' => 'Preparar material do eixo Gastronomia',
                    'descricao' => 'Organizar documentos e fichas dos cursos.',
                    'position' => 0,
                ],
                [
                    'titulo' => 'Consolidar relatório mensal',
                    'descricao' => 'Reunir indicadores das ações da CPED.',
                    'position' => 1,
                ],
            ],
            'Concluído' => [
                [
                    'titulo' => 'Definir colunas do Kanban',
                    'descricao' => 'Estrutura inicial do quadro de atividades.',
                    'position' => 0,
                ],
                [
                    'titulo' => 'Cadastrar equipe no sistema',
                    'descricao' => 'Seed dos 24 membros da CPED concluído.',
                    'position' => 1,
                ],
            ],
        ];

        foreach ($exemplos as $tituloColuna => $cartoes) {
            $coluna = $colunasPorTitulo[$tituloColuna] ?? null;

            if (! $coluna) {
                continue;
            }

            foreach ($cartoes as $cartao) {
                KanbanCartao::query()->create([
                    'kanban_coluna_id' => $coluna->id,
                    'titulo' => $cartao['titulo'],
                    'descricao' => $cartao['descricao'],
                    'position' => $cartao['position'],
                    'criado_por' => null,
                ]);
            }
        }
    }
}
