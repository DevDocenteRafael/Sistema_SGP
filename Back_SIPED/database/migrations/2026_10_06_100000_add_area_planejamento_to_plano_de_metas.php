<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 05 — área/origem do planejamento (Planejamento Estratégico, DN, DEF, CPED).
 * Opcional: registros existentes ficam "sem área" até o cliente classificar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plano_de_metas', 'area_planejamento')) {
            Schema::table('plano_de_metas', function (Blueprint $table) {
                $table->string('area_planejamento', 60)->nullable()->after('origem');
                $table->index('area_planejamento', 'plano_de_metas_area_planejamento_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plano_de_metas', 'area_planejamento')) {
            Schema::table('plano_de_metas', function (Blueprint $table) {
                $table->dropIndex('plano_de_metas_area_planejamento_index');
                $table->dropColumn('area_planejamento');
            });
        }
    }
};
