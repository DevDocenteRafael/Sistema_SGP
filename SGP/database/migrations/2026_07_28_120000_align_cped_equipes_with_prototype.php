<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinha cped_equipes ao organograma / carômetro do protótipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cped_equipes')) {
            return;
        }

        Schema::table('cped_equipes', function (Blueprint $table) {
            if (! Schema::hasColumn('cped_equipes', 'nome')) {
                $table->string('nome', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('cped_equipes', 'cargo')) {
                $table->string('cargo', 100)->nullable()->after('nome');
            }

            if (! Schema::hasColumn('cped_equipes', 'setor')) {
                $table->string('setor', 100)->nullable()->after('cargo');
            }

            if (! Schema::hasColumn('cped_equipes', 'contato')) {
                $table->string('contato', 100)->nullable()->after('setor');
            }

            if (! Schema::hasColumn('cped_equipes', 'tipo')) {
                $table->string('tipo', 50)->nullable()->after('contato');
            }

            if (! Schema::hasColumn('cped_equipes', 'eixo_vinculado')) {
                $table->string('eixo_vinculado', 100)->nullable()->after('tipo');
            }

            if (! Schema::hasColumn('cped_equipes', 'iniciais')) {
                $table->string('iniciais', 20)->nullable()->after('eixo_vinculado');
            }

            if (! Schema::hasColumn('cped_equipes', 'foto')) {
                $table->text('foto')->nullable()->after('iniciais');
            }

            if (! Schema::hasColumn('cped_equipes', 'cor')) {
                $table->string('cor', 20)->nullable()->after('foto');
            }

            if (! Schema::hasColumn('cped_equipes', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('cor');
            }

            if (! Schema::hasColumn('cped_equipes', 'observacao')) {
                $table->text('observacao')->nullable()->after('ativo');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cped_equipes')) {
            return;
        }

        $colunas = [
            'nome',
            'cargo',
            'setor',
            'contato',
            'tipo',
            'eixo_vinculado',
            'iniciais',
            'foto',
            'cor',
            'ativo',
            'observacao',
        ];

        Schema::table('cped_equipes', function (Blueprint $table) use ($colunas) {
            foreach ($colunas as $coluna) {
                if (Schema::hasColumn('cped_equipes', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
