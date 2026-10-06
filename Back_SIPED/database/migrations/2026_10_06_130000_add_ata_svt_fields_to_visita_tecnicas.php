<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitas Técnicas alinhadas à ATA de 30/09/2026 (Sistema de Visitas Técnicas — SVT).
 * Campos preenchidos pelo SVT e exibidos no SIPED para consulta. Todos opcionais.
 */
return new class extends Migration
{
    private const COLUNAS = [
        'turma', 'instrutor', 'curso', 'tipo_curso', 'local_visita',
        'visitas_utilizadas', 'visitas_limite', 'justificativa_excedente', 'motivo_recusa',
        'decisoes', 'transporte', 'relatorio_arquivo_nome', 'relatorio_url',
    ];

    public function up(): void
    {
        Schema::table('visita_tecnicas', function (Blueprint $table) {
            $novas = [
                'turma' => fn () => $table->string('turma', 120)->nullable(),
                'instrutor' => fn () => $table->string('instrutor', 150)->nullable(),
                'curso' => fn () => $table->string('curso', 255)->nullable(),
                'tipo_curso' => fn () => $table->string('tipo_curso', 100)->nullable(),
                'local_visita' => fn () => $table->string('local_visita', 255)->nullable(),
                'visitas_utilizadas' => fn () => $table->unsignedSmallInteger('visitas_utilizadas')->nullable(),
                'visitas_limite' => fn () => $table->unsignedSmallInteger('visitas_limite')->nullable(),
                'justificativa_excedente' => fn () => $table->text('justificativa_excedente')->nullable(),
                'motivo_recusa' => fn () => $table->text('motivo_recusa')->nullable(),
                // Aprovações/recusas de cada etapa do fluxo (lista vinda do SVT).
                'decisoes' => fn () => $table->json('decisoes')->nullable(),
                // Dados do transporte definidos pelo NULOG.
                'transporte' => fn () => $table->json('transporte')->nullable(),
                'relatorio_arquivo_nome' => fn () => $table->string('relatorio_arquivo_nome', 255)->nullable(),
                'relatorio_url' => fn () => $table->string('relatorio_url', 500)->nullable(),
            ];
            foreach ($novas as $coluna => $criar) {
                if (! Schema::hasColumn('visita_tecnicas', $coluna)) {
                    $criar();
                }
            }
        });

        Schema::table('visita_tecnicas', function (Blueprint $table) {
            $table->index(['curso', 'turma'], 'visita_tecnicas_curso_turma_index');
        });
    }

    public function down(): void
    {
        Schema::table('visita_tecnicas', function (Blueprint $table) {
            $table->dropIndex('visita_tecnicas_curso_turma_index');
        });

        Schema::table('visita_tecnicas', function (Blueprint $table) {
            foreach (self::COLUNAS as $coluna) {
                if (Schema::hasColumn('visita_tecnicas', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
