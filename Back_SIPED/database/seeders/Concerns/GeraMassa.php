<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait GeraMassa
{
    /**
     * @param  list<array<string, mixed>>  $linhas
     */
    protected function inserirLotes(string $tabela, array $linhas, int $tamanho = 200): void
    {
        foreach (array_chunk($linhas, $tamanho) as $lote) {
            DB::table($tabela)->insert($lote);
        }
    }

    protected function agora(): string
    {
        return now()->toDateTimeString();
    }

    /**
     * @template T
     * @param  list<T>  $itens
     * @return T
     */
    protected function item(array $itens, int $indice)
    {
        return $itens[$indice % count($itens)];
    }

    /**
     * @param  array<string, int>  $pesos
     */
    protected function statusPorPeso(array $pesos, int $indice): string
    {
        $total = array_sum($pesos) ?: 1;
        $posicao = $indice % $total;
        $acumulado = 0;
        foreach ($pesos as $status => $peso) {
            $acumulado += $peso;
            if ($posicao < $acumulado) {
                return (string) $status;
            }
        }

        return (string) array_key_first($pesos);
    }

    protected function origemSeeder(): array
    {
        return [
            'source_type' => 'seeder',
            'source_system' => null,
            'external_id' => null,
            'synced_at' => null,
        ];
    }

    protected function cpfFicticio(int $indice): string
    {
        return str_pad((string) (10000000000 + $indice), 11, '0', STR_PAD_LEFT);
    }
}
