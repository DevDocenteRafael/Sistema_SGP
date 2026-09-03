<?php

namespace Database\Seeders;

use App\Models\Resolucao;
use App\Services\ResolucaoVigenciaService;
use Illuminate\Database\Seeder;

class ResolucaoSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'numero' => 'MEC/2025/014',
                'curso_relacionado' => 'Técnico em Desenvolvimento Web',
                'categoria' => 'Normativa',
                'resumo' => 'Aprovação do curso técnico em Desenvolvimento Web com carga horária de 1200h.',
                'relator' => 'Maria Souza',
                'setor' => 'CPED',
                'data_inicio_vigencia' => '2025-01-15',
                'status' => 'vigente',
                'observacoes' => 'Resolução em vigência regular.',
            ],
            [
                'numero' => 'MEC/2021/087',
                'curso_relacionado' => 'Técnico em Segurança do Trabalho',
                'categoria' => 'Regulamentação',
                'resumo' => 'Renovação de reconhecimento do curso técnico em Segurança do Trabalho.',
                'relator' => 'Carlos Mendes',
                'setor' => 'Coordenação',
                'data_inicio_vigencia' => '2021-02-01',
                'data_fim_vigencia' => '2026-12-10',
                'status' => 'atencao',
                'observacoes' => 'Vigência próxima do vencimento — iniciar renovação.',
            ],
            [
                'numero' => 'MEC/2021/042',
                'curso_relacionado' => 'Técnico em Nutrição',
                'categoria' => 'Normativa',
                'resumo' => 'Autorização de funcionamento do curso técnico em Nutrição e Dietética.',
                'relator' => 'Ana Paula Lima',
                'setor' => 'Gabinete',
                'data_inicio_vigencia' => '2021-09-01',
                'data_fim_vigencia' => '2026-09-20',
                'status' => 'critico',
                'observacoes' => 'Prazo crítico — ação imediata necessária.',
            ],
            [
                'numero' => 'MEC/2018/203',
                'curso_relacionado' => 'Técnico em Radiologia',
                'categoria' => 'Operacional',
                'resumo' => 'Reconhecimento do curso técnico em Radiologia.',
                'relator' => 'Erika Figueiredo',
                'setor' => 'Diretoria',
                'data_inicio_vigencia' => '2018-03-01',
                'data_fim_vigencia' => '2026-01-01',
                'status' => 'vencida',
                'observacoes' => 'Vigência expirada — regularização pendente.',
            ],
            [
                'numero' => 'MEC/2019/055',
                'curso_relacionado' => 'Qualificação em Barista',
                'categoria' => 'Interna',
                'resumo' => 'Encerramento formal do processo de qualificação em Barista.',
                'relator' => 'Bruno Lima',
                'setor' => 'CPED',
                'data_inicio_vigencia' => '2019-06-01',
                'data_fim_vigencia' => '2024-06-01',
                'status' => 'concluida',
                'observacoes' => 'Processo concluído e arquivado.',
            ],
        ];

        foreach ($registros as $registro) {
            if (! isset($registro['data_fim_vigencia']) && isset($registro['data_inicio_vigencia'])) {
                $registro['data_fim_vigencia'] = ResolucaoVigenciaService::calcularDataFimVigencia(
                    $registro['data_inicio_vigencia']
                )->format('Y-m-d');
            }

            Resolucao::query()->updateOrCreate(
                ['numero' => $registro['numero']],
                $registro
            );
        }
    }
}
