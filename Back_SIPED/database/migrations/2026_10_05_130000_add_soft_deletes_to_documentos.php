<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 03 — exclusão lógica para documentos PDF (demais tabelas: 2026_09_09_185900).
 * Não altera nem apaga dados existentes.
 */
return new class extends Migration
{
    // Documentos PDF são criados depois das demais tabelas (2026_10_05_100200).
    private const TABELAS = [
        'documentos',
    ];

    public function up(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                if (! Schema::hasColumn($tabela, 'deleted_at')) {
                    $table->softDeletes();
                    $table->index('deleted_at', $tabela.'_deleted_at_index');
                }
                if (! Schema::hasColumn($tabela, 'excluido_por')) {
                    $table->unsignedBigInteger('excluido_por')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                if (Schema::hasColumn($tabela, 'deleted_at')) {
                    $table->dropIndex($tabela.'_deleted_at_index');
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn($tabela, 'excluido_por')) {
                    $table->dropColumn('excluido_por');
                }
            });
        }
    }
};
