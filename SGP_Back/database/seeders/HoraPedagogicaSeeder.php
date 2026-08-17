<?php

namespace Database\Seeders;

use App\Models\HoraPedagogica;
use Illuminate\Database\Seeder;

class HoraPedagogicaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'matricula' => '2026001',
                'pessoa' => 'Ana Souza',
                'segmento' => 'Tecnologia e Economia Criativa',
                'eixo' => 'Tecnologia e Economia Criativa',
                'processo_sei' => '00002.000111/2026-01',
                'ano' => 2026,
                'motivo' => 'Preparação de material didático para módulo híbrido',
                'status' => 'Concluída',
                'ativo' => true,
                'observacao' => 'Horas validadas pela coordenação.',
            ],
            [
                'matricula' => '2026002',
                'pessoa' => 'Bruno Lima',
                'segmento' => 'Gastronomia',
                'eixo' => 'Gastronomia',
                'processo_sei' => '00002.000222/2026-02',
                'ano' => 2026,
                'motivo' => 'Acompanhamento pedagógico de turma presencial',
                'status' => 'Em andamento',
                'ativo' => true,
                'observacao' => 'Pendência na documentação complementar.',
            ],
            [
                'matricula' => '2026003',
                'pessoa' => 'Carla Mendes',
                'segmento' => 'Ambiente e Saúde',
                'eixo' => 'Ambiente e Saúde',
                'processo_sei' => '00002.000333/2026-03',
                'ano' => 2025,
                'motivo' => 'Reunião de alinhamento de competências',
                'status' => 'Pendente',
                'ativo' => true,
                'observacao' => 'Aguardando liberação no SEI.',
            ],
            [
                'matricula' => '2026004',
                'pessoa' => 'Daniel Rego',
                'segmento' => 'Beleza e Cuidado Pessoal',
                'eixo' => 'Beleza e Cuidado Pessoal',
                'processo_sei' => '00002.000444/2026-04',
                'ano' => 2026,
                'motivo' => 'Monitoria e apoio ao processo formativo',
                'status' => 'Cancelada',
                'ativo' => false,
                'observacao' => 'Solicitação cancelada por ajuste de agenda.',
            ],
        ];

        foreach ($registros as $registro) {
            HoraPedagogica::query()->updateOrCreate(
                ['processo_sei' => $registro['processo_sei']],
                $registro
            );
        }
    }
}
