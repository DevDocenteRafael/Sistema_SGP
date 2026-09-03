<?php

namespace Database\Seeders;

use App\Models\JornadaPedagogica;
use Illuminate\Database\Seeder;

class JornadaPedagogicaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'titulo' => 'Jornada Pedagógica 2026 — Integração de Eixos',
                'data_inicio' => '2026-08-25',
                'data_fim' => '2026-08-27',
                'tem_pre_jornada' => 'Sim',
                'data_pre_jornada' => '2026-08-22',
                'local' => 'Centro de Eventos Senac DF',
                'espaco' => 'Auditório principal',
                'verba' => 'R$ 45.000,00',
                'custos' => 'Coffee break, material didático e honorários de palestrantes.',
                'programacao' => 'Dia 1: abertura e alinhamento estratégico. Dia 2: oficinas por eixo. Dia 3: consolidação e encaminhamentos.',
                'setores' => 'CPED, Coordenações de eixo, RH',
                'observacoes' => 'Evento anual de integração pedagógica.',
                'status' => 'Consolidado',
            ],
            [
                'titulo' => 'Pré-jornada — Inovação e Tecnologia na Educação',
                'data_inicio' => '2026-09-15',
                'data_fim' => '2026-09-15',
                'tem_pre_jornada' => 'Não',
                'local' => 'Unidade Asa Norte',
                'espaco' => 'Sala de videoconferência',
                'verba' => 'R$ 3.500,00',
                'programacao' => 'Manhã: ferramentas de IA na prática pedagógica. Tarde: compartilhamento de experiências.',
                'setores' => 'Tecnologia e Economia Criativa',
                'status' => 'Enviado',
            ],
            [
                'titulo' => 'Jornada Regional — Saúde e Bem-estar',
                'data_inicio' => '2026-10-08',
                'data_fim' => '2026-10-09',
                'tem_pre_jornada' => 'Sim',
                'data_pre_jornada' => '2026-10-06',
                'local' => 'Unidade Sobradinho',
                'espaco' => 'Laboratórios e auditório',
                'verba' => 'R$ 18.000,00',
                'programacao' => 'Workshops práticos com foco em simulação realística e protocolos atualizados.',
                'setores' => 'Saúde, Enfermagem, Nutrição',
                'observacoes' => 'Aguardando confirmação de palestrantes externos.',
                'status' => 'Rascunho',
            ],
            [
                'titulo' => 'Encontro Pedagógico — Gastronomia e Hospitalidade',
                'data_inicio' => '2026-07-14',
                'data_fim' => '2026-07-15',
                'tem_pre_jornada' => 'Não',
                'local' => 'Unidade Taguatinga',
                'espaco' => 'Cozinha didática',
                'verba' => 'R$ 12.000,00',
                'custos' => 'Insumos para oficinas práticas.',
                'programacao' => 'Oficinas de técnicas contemporâneas e gestão de cozinha profissional.',
                'setores' => 'Gastronomia e Turismo',
                'observacoes' => 'Realizada com participação de 48 docentes.',
                'status' => 'Consolidado',
            ],
        ];

        foreach ($registros as $registro) {
            JornadaPedagogica::query()->updateOrCreate(
                ['titulo' => $registro['titulo']],
                $registro
            );
        }
    }
}
