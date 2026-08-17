<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa curso_por_eixos se a tabela já existia só com id/timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curso_por_eixos')) {
            return;
        }

        Schema::table('curso_por_eixos', function (Blueprint $table) {
            if (! Schema::hasColumn('curso_por_eixos', 'curso')) {
                $table->string('curso', 255)->nullable()->after('id');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'eixo')) {
                $table->string('eixo', 150)->nullable()->after('curso');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'unidade')) {
                $table->string('unidade', 100)->nullable()->after('eixo');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'ano')) {
                $table->string('ano', 4)->nullable()->after('unidade');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'ch')) {
                $table->string('ch', 50)->nullable()->after('ano');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'turmas')) {
                $table->string('turmas', 20)->nullable()->after('ch');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'codigo')) {
                $table->string('codigo', 100)->nullable()->after('turmas');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'alunos')) {
                $table->string('alunos', 20)->nullable()->after('codigo');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'instrutores')) {
                $table->string('instrutores', 255)->nullable()->after('alunos');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'status')) {
                $table->string('status', 50)->default('Ativo')->after('instrutores');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'observacao')) {
                $table->text('observacao')->nullable()->after('status');
            }

            if (! Schema::hasColumn('curso_por_eixos', 'is_novo')) {
                $table->boolean('is_novo')->default(false)->after('observacao');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('curso_por_eixos')) {
            return;
        }

        Schema::table('curso_por_eixos', function (Blueprint $table) {
            $colunas = [
                'curso',
                'eixo',
                'unidade',
                'ano',
                'ch',
                'turmas',
                'codigo',
                'alunos',
                'instrutores',
                'status',
                'observacao',
                'is_novo',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('curso_por_eixos', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
