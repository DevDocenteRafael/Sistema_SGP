<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renomear('Ensino Médio 2025', 'Ensino Médio');
    }

    public function down(): void
    {
        $this->renomear('Ensino Médio', 'Ensino Médio 2025');
    }

    private function renomear(string $de, string $para): void
    {
        foreach (['cursos', 'curso_por_eixos'] as $tabela) {
            if (Schema::hasTable($tabela) && Schema::hasColumn($tabela, 'programa')) {
                DB::table($tabela)->where('programa', $de)->update(['programa' => $para]);
            }
        }
    }
};
