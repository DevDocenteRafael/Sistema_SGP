<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa plano_de_metas se a tabela já existia só com id/timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plano_de_metas')) {
            return;
        }

        Schema::table('plano_de_metas', function (Blueprint $table) {
            if (! Schema::hasColumn('plano_de_metas', 'segmento')) {
                $table->string('segmento', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('plano_de_metas', 'curso')) {
                $table->string('curso', 255)->nullable()->after('segmento');
            }

            if (! Schema::hasColumn('plano_de_metas', 'tipo')) {
                $table->string('tipo', 100)->nullable()->after('curso');
            }

            if (! Schema::hasColumn('plano_de_metas', 'numero_sei')) {
                $table->string('numero_sei', 100)->nullable()->unique()->after('tipo');
            }

            if (! Schema::hasColumn('plano_de_metas', 'codigo_sig')) {
                $table->string('codigo_sig', 100)->nullable()->unique()->after('numero_sei');
            }

            if (! Schema::hasColumn('plano_de_metas', 'mes_entrega')) {
                $table->string('mes_entrega', 50)->nullable()->after('codigo_sig');
            }

            if (! Schema::hasColumn('plano_de_metas', 'status')) {
                $table->string('status', 50)->nullable()->after('mes_entrega');
            }

            if (! Schema::hasColumn('plano_de_metas', 'origem')) {
                $table->string('origem', 100)->nullable()->after('status');
            }

            if (! Schema::hasColumn('plano_de_metas', 'status_final')) {
                $table->string('status_final', 50)->nullable()->after('origem');
            }

            if (! Schema::hasColumn('plano_de_metas', 'observacao')) {
                $table->text('observacao')->nullable()->after('status_final');
            }

            if (! Schema::hasColumn('plano_de_metas', 'ano')) {
                $table->integer('ano')->nullable()->after('observacao');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plano_de_metas')) {
            return;
        }

        Schema::table('plano_de_metas', function (Blueprint $table) {
            $colunas = [
                'segmento',
                'curso',
                'tipo',
                'numero_sei',
                'codigo_sig',
                'mes_entrega',
                'status',
                'origem',
                'status_final',
                'observacao',
                'ano',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('plano_de_metas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
