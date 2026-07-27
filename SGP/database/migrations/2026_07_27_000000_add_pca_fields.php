<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa pcas se a tabela já existia só com id/timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pcas')) {
            return;
        }

        Schema::table('pcas', function (Blueprint $table) {
            if (! Schema::hasColumn('pcas', 'unidade')) {
                $table->string('unidade', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('pcas', 'curso')) {
                $table->string('curso', 255)->nullable()->after('unidade');
            }

            if (! Schema::hasColumn('pcas', 'tipo')) {
                $table->string('tipo', 100)->nullable()->after('curso');
            }

            if (! Schema::hasColumn('pcas', 'periodo')) {
                $table->string('periodo', 100)->nullable()->after('tipo');
            }

            if (! Schema::hasColumn('pcas', 'numero_sei')) {
                $table->string('numero_sei', 100)->nullable()->unique()->after('periodo');
            }

            if (! Schema::hasColumn('pcas', 'codigo_sig')) {
                $table->string('codigo_sig', 100)->nullable()->unique()->after('numero_sei');
            }

            if (! Schema::hasColumn('pcas', 'status')) {
                $table->string('status', 50)->nullable()->after('codigo_sig');
            }

            if (! Schema::hasColumn('pcas', 'responsavel')) {
                $table->string('responsavel', 255)->nullable()->after('status');
            }

            if (! Schema::hasColumn('pcas', 'objetivo')) {
                $table->text('objetivo')->nullable()->after('responsavel');
            }

            if (! Schema::hasColumn('pcas', 'data_inicio')) {
                $table->date('data_inicio')->nullable()->after('objetivo');
            }

            if (! Schema::hasColumn('pcas', 'data_fim')) {
                $table->date('data_fim')->nullable()->after('data_inicio');
            }

            if (! Schema::hasColumn('pcas', 'observacao')) {
                $table->text('observacao')->nullable()->after('data_fim');
            }

            if (! Schema::hasColumn('pcas', 'ano')) {
                $table->integer('ano')->nullable()->after('observacao');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pcas')) {
            return;
        }

        Schema::table('pcas', function (Blueprint $table) {
            $colunas = [
                'unidade',
                'curso',
                'tipo',
                'periodo',
                'numero_sei',
                'codigo_sig',
                'status',
                'responsavel',
                'objetivo',
                'data_inicio',
                'data_fim',
                'observacao',
                'ano',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('pcas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
