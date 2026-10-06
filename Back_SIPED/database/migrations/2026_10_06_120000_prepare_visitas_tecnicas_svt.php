<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 07 — Visitas Técnicas preparadas para virem do SVT.
 * etapa_svt: etapa do fluxo no SVT (Instrutor, Coordenação, CPAD, DEP, NULOG).
 * Índice único (source_system, external_id): uma visita do SVT vira um único registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visita_tecnicas', function (Blueprint $table) {
            if (! Schema::hasColumn('visita_tecnicas', 'etapa_svt')) {
                $table->string('etapa_svt', 60)->nullable()->after('status');
            }
            $table->unique(['source_system', 'external_id'], 'visita_tecnicas_origem_externa_unique');
        });
    }

    public function down(): void
    {
        Schema::table('visita_tecnicas', function (Blueprint $table) {
            $table->dropUnique('visita_tecnicas_origem_externa_unique');
            if (Schema::hasColumn('visita_tecnicas', 'etapa_svt')) {
                $table->dropColumn('etapa_svt');
            }
        });
    }
};
