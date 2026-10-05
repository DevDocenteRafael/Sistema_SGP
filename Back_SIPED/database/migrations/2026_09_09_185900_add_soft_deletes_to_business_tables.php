<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 03 — exclusão lógica: deleted_at + quem excluiu, nos registros de negócio.
 * Não altera nem apaga dados existentes.
 *
 * Data anterior de propósito: as migrations de 2026_09_09 usam os models (Ciclo/Curso)
 * via Eloquent, que passam a filtrar deleted_at. Em bancos já existentes ela roda
 * normalmente como pendente.
 */
return new class extends Migration
{
    private const TABELAS = [
        'cursos',
        'curso_por_eixos',
        'plano_de_metas',
        'pcas',
        'visita_tecnicas',
        'hora_pedagogicas',
        'acao_extensivas',
        'eventos',
        'jornadas_pedagogicas',
        'resolucoes',
        'termos_referencia',
        'portfolio_ciclos',
        'cped_equipes',
        'kanban_quadros',
        'kanban_colunas',
        'kanban_cartoes',
        'fluxogramas',
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
