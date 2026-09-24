<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncarDadosOperacionaisSeeder extends Seeder
{
    public function run(): void
    {
        $tabelas = [
            'cadastros',
            'resolucao_historicos',
            'termos_referencia_historicos',
            'kanban_cartoes',
            'kanban_colunas',
            'kanban_quadros',
            'fluxogramas',
            'curso_por_eixos',
            'cursos',
            'plano_de_metas',
            'pcas',
            'visita_tecnicas',
            'hora_pedagogicas',
            'acao_extensivas',
            'eventos',
            'resolucoes',
            'termos_referencia',
            'jornadas_pedagogicas',
            'cped_equipes',
            'unidades_oferta',
            'sincronizacao_historicos',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tabelas as $tabela) {
            if (Schema::hasTable($tabela)) {
                DB::table($tabela)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();
    }
}
