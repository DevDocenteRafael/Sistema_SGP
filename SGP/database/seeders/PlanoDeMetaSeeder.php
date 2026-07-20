<?php

namespace Database\Seeders;

use App\Models\PlanoDeMeta;
use Illuminate\Database\Seeder;

class PlanoDeMetaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'segmento' => 'Infraestrutura',
                'curso' => 'Gestão de Processos',
                'tipo' => 'QUALIFICAÇÃO',
                'numero_sei' => 'SEI-1001',
                'codigo_sig' => 'SIG-1001',
                'mes_entrega' => 'Janeiro',
                'status' => 'EM ANÁLISE',
                'origem' => 'Plano de Metas',
                'status_final' => 'PENDENTE',
                'observacao' => 'Registro de exemplo para apresentação do portfólio.',
                'ano' => 2026,
            ],
            [
                'segmento' => 'Educação',
                'curso' => 'Liderança e Comunicação',
                'tipo' => 'PRESENCIAL',
                'numero_sei' => 'SEI-1002',
                'codigo_sig' => 'SIG-1002',
                'mes_entrega' => 'Fevereiro',
                'status' => 'CONCLUÍDO',
                'origem' => 'Plano de Metas',
                'status_final' => 'PUBLICADO',
                'observacao' => 'Ação já concluída e publicada para acompanhamento.',
                'ano' => 2026,
            ],
            [
                'segmento' => 'Infraestrutura',
                'curso' => 'Mapeamento de Capacitação',
                'tipo' => 'HÍBRIDO',
                'numero_sei' => 'SEI-1003',
                'codigo_sig' => 'SIG-1003',
                'mes_entrega' => 'Março',
                'status' => 'EM ANDAMENTO',
                'origem' => 'Plano de Metas',
                'status_final' => 'ENTREGUE',
                'observacao' => 'Registro em andamento com entrega parcial validada.',
                'ano' => 2027,
            ],
            [
                'segmento' => 'Educação',
                'curso' => 'Planejamento Estratégico',
                'tipo' => 'QUALIFICAÇÃO',
                'numero_sei' => 'SEI-1004',
                'codigo_sig' => 'SIG-1004',
                'mes_entrega' => 'Abril',
                'status' => 'PLANEJADO',
                'origem' => 'Plano de Metas',
                'status_final' => 'EM ANALISE',
                'observacao' => 'Ação em análise para validação final do portfólio.',
                'ano' => 2027,
            ],
        ];

        foreach ($registros as $registro) {
            PlanoDeMeta::query()->firstOrCreate(
                ['numero_sei' => $registro['numero_sei']],
                $registro
            );
        }
    }
}
