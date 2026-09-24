<?php

namespace Database\Seeders;

use App\Models\PortfolioCiclo;
use Illuminate\Database\Seeder;

class CicloSeeder extends Seeder
{
    public function run(): void
    {
        $ciclos = [
            ['nome' => '2024', 'atual' => false, 'observacao' => 'Ciclo histórico para consulta.'],
            ['nome' => '2025-2026', 'atual' => true, 'observacao' => 'Ciclo de gestão atual.'],
            ['nome' => '2026', 'atual' => false, 'observacao' => 'Ciclo complementar de planejamento.'],
            ['nome' => '2027', 'atual' => false, 'observacao' => 'Ciclo futuro / histórico de testes.'],
        ];

        foreach ($ciclos as $dados) {
            PortfolioCiclo::query()->firstOrCreate(
                ['nome' => $dados['nome']],
                [
                    'atual' => $dados['atual'],
                    'observacao' => $dados['observacao'],
                ]
            );
        }

        $atual = PortfolioCiclo::query()->where('nome', '2025-2026')->first();
        if ($atual) {
            PortfolioCiclo::query()->where('id', '!=', $atual->id)->update(['atual' => false]);
            $atual->update(['atual' => true]);
        }
    }
}
