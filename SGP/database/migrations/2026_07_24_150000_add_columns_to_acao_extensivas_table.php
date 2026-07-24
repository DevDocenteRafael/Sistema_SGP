<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa acao_extensivas com colunas da planilha "Ações extensivas".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acao_extensivas')) {
            return;
        }

        Schema::table('acao_extensivas', function (Blueprint $table) {
            if (! Schema::hasColumn('acao_extensivas', 'priorizacao')) {
                $table->string('priorizacao', 20)->nullable()->after('id');
            }

            if (! Schema::hasColumn('acao_extensivas', 'atribuido')) {
                $table->string('atribuido', 100)->nullable()->after('priorizacao');
            }

            if (! Schema::hasColumn('acao_extensivas', 'eixo')) {
                $table->string('eixo', 150)->nullable()->after('atribuido');
            }

            if (! Schema::hasColumn('acao_extensivas', 'numero_processo_sei')) {
                $table->string('numero_processo_sei', 50)->nullable()->after('eixo');
            }

            if (! Schema::hasColumn('acao_extensivas', 'tipo')) {
                $table->string('tipo', 100)->nullable()->after('numero_processo_sei');
            }

            if (! Schema::hasColumn('acao_extensivas', 'assunto')) {
                $table->string('assunto', 500)->nullable()->after('tipo');
            }

            if (! Schema::hasColumn('acao_extensivas', 'objetivo')) {
                $table->text('objetivo')->nullable()->after('assunto');
            }

            if (! Schema::hasColumn('acao_extensivas', 'status')) {
                $table->string('status', 50)->nullable()->after('objetivo');
            }

            if (! Schema::hasColumn('acao_extensivas', 'ultima_atualizacao')) {
                $table->date('ultima_atualizacao')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('acao_extensivas')) {
            return;
        }

        Schema::table('acao_extensivas', function (Blueprint $table) {
            $colunas = [
                'priorizacao',
                'atribuido',
                'eixo',
                'numero_processo_sei',
                'tipo',
                'assunto',
                'objetivo',
                'status',
                'ultima_atualizacao',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('acao_extensivas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
