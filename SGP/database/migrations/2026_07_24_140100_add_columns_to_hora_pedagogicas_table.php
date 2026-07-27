<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa hora_pedagogicas se a tabela já existia só com id/timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hora_pedagogicas')) {
            return;
        }

        Schema::table('hora_pedagogicas', function (Blueprint $table) {
            if (! Schema::hasColumn('hora_pedagogicas', 'matricula')) {
                $table->string('matricula', 50)->nullable()->after('id');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'pessoa')) {
                $table->string('pessoa', 150)->nullable()->after('matricula');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'segmento')) {
                $table->string('segmento', 150)->nullable()->after('pessoa');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'eixo')) {
                $table->string('eixo', 150)->nullable()->after('segmento');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'processo_sei')) {
                $table->string('processo_sei', 100)->nullable()->after('eixo');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'ano')) {
                $table->unsignedSmallInteger('ano')->nullable()->after('processo_sei');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'motivo')) {
                $table->string('motivo', 255)->nullable()->after('ano');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'status')) {
                $table->string('status', 50)->nullable()->after('motivo');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('status');
            }

            if (! Schema::hasColumn('hora_pedagogicas', 'observacao')) {
                $table->text('observacao')->nullable()->after('ativo');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hora_pedagogicas')) {
            return;
        }

        Schema::table('hora_pedagogicas', function (Blueprint $table) {
            $colunas = [
                'matricula',
                'pessoa',
                'segmento',
                'eixo',
                'processo_sei',
                'ano',
                'motivo',
                'status',
                'ativo',
                'observacao',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('hora_pedagogicas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
