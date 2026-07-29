<?php

namespace Database\Seeders;

use App\Models\Pca;
use Illuminate\Database\Seeder;

class PcaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'titulo' => 'Técnico em Administração',
                'semestre' => '2026/1',
                'numero_sei' => 'SEI-PCA-001',
                'codigo_sig' => 'SIG-PCA-001',
                'eixo' => 'Gestão e Moda',
                'unidade' => 'Taguatinga',
                'carga_horaria' => '1200',
                'precificacao' => 'R$ 4.800,00',
                'valor_primeiro_modulo' => 'R$ 800,00',
                'valor' => 'R$ 4.800,00',
                'parcelas_boleto' => '12',
                'valor_parcela_boleto' => 'R$ 400,00',
                'parcelas_cartao' => '10',
                'valor_cartao' => 'R$ 480,00',
                'parcela_desc_20' => 'R$ 320,00',
                'parcela_desc_15' => 'R$ 340,00',
                'status' => 'Vigente',
                'observacao' => 'Curso vigente no planejamento do período.',
                'ano' => 2026,
            ],
            [
                'titulo' => 'Técnico em Enfermagem',
                'semestre' => '2026/2',
                'numero_sei' => 'SEI-PCA-002',
                'codigo_sig' => 'SIG-PCA-002',
                'eixo' => 'Ambiente e Saúde',
                'unidade' => 'Sobradinho',
                'carga_horaria' => '1600',
                'precificacao' => 'R$ 6.200,00',
                'valor_primeiro_modulo' => 'R$ 1.000,00',
                'valor' => 'R$ 6.200,00',
                'parcelas_boleto' => '12',
                'valor_parcela_boleto' => 'R$ 516,67',
                'parcelas_cartao' => '10',
                'valor_cartao' => 'R$ 620,00',
                'parcela_desc_20' => 'R$ 413,33',
                'parcela_desc_15' => 'R$ 439,17',
                'status' => 'Em análise',
                'observacao' => 'Aguardando validação da precificação.',
                'ano' => 2026,
            ],
            [
                'titulo' => 'Gastronomia Contemporânea',
                'semestre' => '2025/2',
                'numero_sei' => 'SEI-PCA-003',
                'codigo_sig' => 'SIG-PCA-003',
                'eixo' => 'Gastronomia',
                'unidade' => 'Jessé Freire',
                'carga_horaria' => '360',
                'precificacao' => 'R$ 2.400,00',
                'valor_primeiro_modulo' => 'R$ 400,00',
                'valor' => 'R$ 2.400,00',
                'parcelas_boleto' => '6',
                'valor_parcela_boleto' => 'R$ 400,00',
                'parcelas_cartao' => '6',
                'valor_cartao' => 'R$ 400,00',
                'parcela_desc_20' => 'R$ 320,00',
                'parcela_desc_15' => 'R$ 340,00',
                'status' => 'Suspenso',
                'observacao' => 'Oferta suspensa temporariamente.',
                'ano' => 2025,
            ],
        ];

        foreach ($registros as $registro) {
            Pca::query()->updateOrCreate(
                ['numero_sei' => $registro['numero_sei']],
                $registro
            );
        }
    }
}
