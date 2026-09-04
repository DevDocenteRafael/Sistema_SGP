<?php

namespace Database\Seeders;

use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
use Illuminate\Database\Seeder;

class UnidadeOfertaSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = array_values(config('unidades', []));

        foreach ($nomes as $nome) {
            $regiao = RegiaoAdministrativa::query()->firstOrCreate(
                ['nome' => $nome],
                ['ativo' => true],
            );

            UnidadeOferta::query()->firstOrCreate(
                [
                    'regiao_administrativa_id' => $regiao->id,
                    'nome' => $nome,
                ],
                [
                    'tipo' => UnidadeOferta::TIPO_CEP,
                    'ativo' => true,
                ],
            );
        }
    }
}
