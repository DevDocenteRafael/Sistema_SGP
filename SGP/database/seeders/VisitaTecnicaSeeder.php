<?php

namespace Database\Seeders;

use App\Models\VisitaTecnica;
use Illuminate\Database\Seeder;

class VisitaTecnicaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'unidade' => 'Asa Norte',
                'eixo' => 'Tecnologia e Economia Criativa',
                'processo_sei' => '00001.000123/2026-01',
                'data_solicitacao' => '2026-06-10',
                'data_visita_prevista' => '2026-07-15',
                'prazo_limite' => '2026-07-20',
                'status' => 'Realizada',
                'responsavel' => 'Ana Souza',
                'relatorio' => 'Visita concluída. Estrutura adequada e equipe alinhada.',
                'observacao' => 'Relatório encaminhado à coordenação.',
            ],
            [
                'unidade' => 'Taguatinga',
                'eixo' => 'Gastronomia',
                'processo_sei' => '00001.000456/2026-02',
                'data_solicitacao' => '2026-07-01',
                'data_visita_prevista' => '2026-07-28',
                'prazo_limite' => '2026-08-05',
                'status' => 'Em andamento',
                'responsavel' => 'Bruno Lima',
                'relatorio' => null,
                'observacao' => 'Aguardando confirmação de horário com a unidade.',
            ],
            [
                'unidade' => 'Gama',
                'eixo' => 'Ambiente e Saúde',
                'processo_sei' => '00001.000789/2026-03',
                'data_solicitacao' => '2026-07-05',
                'data_visita_prevista' => '2026-08-10',
                'prazo_limite' => '2026-08-15',
                'status' => 'Pendente',
                'responsavel' => 'Carla Mendes',
                'relatorio' => null,
                'observacao' => 'Mapeamento inicial das necessidades da unidade.',
            ],
            [
                'unidade' => 'Ceilândia',
                'eixo' => 'Beleza e Cuidado Pessoal',
                'processo_sei' => '00001.000321/2026-04',
                'data_solicitacao' => '2026-05-20',
                'data_visita_prevista' => '2026-06-15',
                'prazo_limite' => '2026-06-20',
                'status' => 'Cancelada',
                'responsavel' => 'Daniel Rego',
                'relatorio' => null,
                'observacao' => 'Cancelada por ajuste de agenda institucional.',
            ],
            [
                'unidade' => 'Sobradinho',
                'eixo' => 'Gestão e Moda',
                'processo_sei' => '00001.000654/2026-05',
                'data_solicitacao' => '2026-06-01',
                'data_visita_prevista' => '2026-06-25',
                'prazo_limite' => '2026-06-30',
                'status' => 'Atrasada',
                'responsavel' => 'Elena Prado',
                'relatorio' => null,
                'observacao' => 'Prazo ultrapassado — reagendamento em análise.',
            ],
        ];

        foreach ($registros as $registro) {
            VisitaTecnica::query()->updateOrCreate(
                ['processo_sei' => $registro['processo_sei']],
                $registro
            );
        }
    }
}
