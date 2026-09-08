<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return list<string>
     */
    private function tabelas(): array
    {
        return ['plano_de_metas', 'pcas', 'curso_por_eixos'];
    }

    public function up(): void
    {
        $cicloId = DB::table('portfolio_ciclos')->where('atual', true)->value('id')
            ?? DB::table('portfolio_ciclos')->orderBy('id')->value('id');

        foreach ($this->tabelas() as $tabela) {
            if (! Schema::hasTable($tabela) || Schema::hasColumn($tabela, 'ciclo_id')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->unsignedBigInteger('ciclo_id')->nullable()->after('id');
                $table->foreign('ciclo_id')->references('id')->on('portfolio_ciclos')->nullOnDelete();
                $table->index('ciclo_id');
            });

            if ($cicloId) {
                DB::table($tabela)->whereNull('ciclo_id')->update(['ciclo_id' => $cicloId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas() as $tabela) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'ciclo_id')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->dropForeign(['ciclo_id']);
                $table->dropColumn('ciclo_id');
            });
        }
    }
};
