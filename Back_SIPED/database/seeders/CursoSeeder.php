<?php

namespace Database\Seeders;

use App\Models\Curso;
use Database\Seeders\Concerns\UsaCicloPortfolio;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    use UsaCicloPortfolio;
    public function run(): void
    {
        $cursos = [
            [
                'titulo' => 'Cozinha Contemporânea',
                'eixo' => 'Gastronomia',
                'modalidade' => 'Presencial',
                'carga_horaria' => '200',
                'turmas' => '2',
                'codigo_processo' => '2025.12.85',
                'alunos' => '22',
                'instrutor' => 'Chef João Silva',
                'descricao' => 'Curso voltado para técnicas contemporâneas de cozinha e gestão de cozinha profissional.',
                'codigo_dn' => '12345',
                'codigo_sig' => 'SIG-2025-001',
                'identificacao' => 'CC-01',
                'tipo' => 'Técnico',
                'status' => 'ATIVO',
                'ultima_revisao' => '2025',
                'processo_sei' => '0001234.567890/2025-01',
                'data_inicio' => '2025-02-01',
                'data_fim' => '2025-12-15',
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'unidades_oferta' => ['Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte', 'Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga'],
                'observacoes' => 'Curso em operação regular.',
                'valores' => '2025 | R$ 1.200,00',
                'compativel_bolsa' => 'SIM',
                'comercial' => 'SIM',
                'pcn' => 'PCN Gastronomia 2024',
                'pcr' => 'PCR DF Gastronomia',
            ],
            [
                'titulo' => 'Segurança do Trabalho',
                'eixo' => 'Ambiente e Saúde',
                'modalidade' => 'Híbrido',
                'carga_horaria' => '160h',
                'codigo_dn' => '23456',
                'codigo_sig' => 'SIG-2025-014',
                'identificacao' => 'ST-02',
                'tipo' => 'Técnico',
                'status' => 'ATIVO',
                'ultima_revisao' => '2025',
                'processo_sei' => '0002345.678901/2025-02',
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga',
                'unidades_oferta' => ['Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga'],
                'observacoes' => null,
                'valores' => 'R$ 980,00',
            ],
            [
                'titulo' => 'Marketing Digital',
                'eixo' => 'Gestão e Moda',
                'modalidade' => 'EAD',
                'carga_horaria' => '120h',
                'codigo_dn' => '34567',
                'codigo_sig' => 'SIG-2025-028',
                'identificacao' => 'MD-03',
                'tipo' => 'Aperfeiçoamento',
                'status' => 'EM REVISÃO',
                'ultima_revisao' => '2024',
                'processo_sei' => '0003456.789012/2024-11',
                'unidade' => 'Gama',
                'observacoes' => 'Aguardando atualização de conteúdo.',
                'valores' => 'R$ 750,00',
            ],
            [
                'titulo' => 'Desenvolvimento Web Full Stack',
                'eixo' => 'Tecnologia e Economia Criativa',
                'modalidade' => 'Presencial',
                'carga_horaria' => '360h',
                'codigo_dn' => '45678',
                'codigo_sig' => 'SIG-2025-042',
                'identificacao' => 'DW-04',
                'tipo' => 'Técnico',
                'status' => 'ATIVO',
                'ultima_revisao' => '2026',
                'processo_sei' => '0004567.890123/2026-01',
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'observacoes' => 'Nova turma prevista para o segundo semestre.',
                'valores' => 'R$ 2.400,00',
            ],
            [
                'titulo' => 'Design de Sobrancelhas',
                'eixo' => 'Beleza e Cuidado Pessoal',
                'modalidade' => 'Presencial',
                'carga_horaria' => '40h',
                'codigo_dn' => '56789',
                'codigo_sig' => 'SIG-2025-055',
                'identificacao' => 'DS-05',
                'tipo' => 'Qualificação',
                'status' => 'INATIVO',
                'ultima_revisao' => '2023',
                'processo_sei' => '0005678.901234/2023-08',
                'unidade' => 'Ceilândia',
                'observacoes' => 'Curso suspenso para reestruturação.',
                'valores' => 'R$ 420,00',
            ],
            [
                'titulo' => 'Guia de Turismo',
                'eixo' => 'Turismo e Hospitalidade',
                'modalidade' => 'Presencial',
                'carga_horaria' => '180h',
                'codigo_dn' => '67890',
                'codigo_sig' => 'SIG-2025-067',
                'identificacao' => 'GT-06',
                'tipo' => 'Técnico',
                'status' => 'ATIVO',
                'ultima_revisao' => '2025',
                'processo_sei' => '0006789.012345/2025-03',
                'unidade' => 'Sobradinho',
                'observacoes' => null,
                'valores' => 'R$ 1.050,00',
            ],
        ];

        foreach ($cursos as $dados) {
            Curso::updateOrCreate(
                ['codigo_sig' => $dados['codigo_sig']],
                $this->comCiclo($dados)
            );
        }
    }
}
