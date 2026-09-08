<?php

namespace Database\Seeders;

use App\Models\TermoReferencia;
use Illuminate\Database\Seeder;

class TermoReferenciaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'nome' => 'TR — Revisão curricular Técnico em Enfermagem',
                'eixo' => 'Saúde',
                'processo_sei' => '2026.01.00045-01',
                'prazo_deadline' => '2026-10-15',
                'status' => 'Em Andamento',
                'observacao' => 'Elaboração do documento base com a coordenação do eixo.',
                'data_inicio' => '2026-03-01',
            ],
            [
                'nome' => 'TR — Novo curso de Inteligência Artificial aplicada',
                'eixo' => 'Tecnologia e Economia Criativa',
                'processo_sei' => '2026.02.00012-03',
                'prazo_deadline' => '2026-11-30',
                'status' => 'Planejamento',
                'observacao' => 'Levantamento de demanda e alinhamento com mercado.',
            ],
            [
                'nome' => 'TR — Adequação de laboratório Gastronomia Asa Norte',
                'eixo' => 'Gastronomia e Turismo',
                'processo_sei' => '2026.03.00078-02',
                'prazo_deadline' => '2026-09-20',
                'status' => 'Em tramitação (fora da CPED)',
                'observacao' => 'Encaminhado ao setor de infraestrutura para orçamento.',
                'data_inicio' => '2026-05-10',
            ],
            [
                'nome' => 'TR — Qualificação em Marketing Digital',
                'eixo' => 'Gestão e Moda',
                'processo_sei' => '2025.12.00456-01',
                'prazo_deadline' => '2026-06-01',
                'status' => 'Concluído',
                'observacao' => 'Documento aprovado e arquivado.',
                'data_inicio' => '2025-08-01',
                'data_fim' => '2026-05-28',
                'concluido_em' => '2026-05-28 14:30:00',
            ],
            [
                'nome' => 'TR — Extensão curricular Beleza e Estética',
                'eixo' => 'Beleza e Cuidado Pessoal',
                'processo_sei' => '2024.09.00234-04',
                'prazo_deadline' => '2025-12-31',
                'status' => 'Arquivado',
                'observacao' => 'Processo encerrado sem continuidade no ciclo atual.',
                'data_inicio' => '2024-06-01',
                'data_fim' => '2025-11-15',
            ],
        ];

        foreach ($registros as $registro) {
            TermoReferencia::query()->updateOrCreate(
                ['processo_sei' => $registro['processo_sei']],
                $registro
            );
        }
    }
}
