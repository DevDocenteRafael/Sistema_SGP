<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa visita_tecnicas se a tabela já existia só com id/timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visita_tecnicas')) {
            return;
        }

        Schema::table('visita_tecnicas', function (Blueprint $table) {
            if (! Schema::hasColumn('visita_tecnicas', 'unidade')) {
                $table->string('unidade', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'eixo')) {
                $table->string('eixo', 150)->nullable()->after('unidade');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'processo_sei')) {
                $table->string('processo_sei', 100)->nullable()->after('eixo');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'data_solicitacao')) {
                $table->date('data_solicitacao')->nullable()->after('processo_sei');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'data_visita_prevista')) {
                $table->date('data_visita_prevista')->nullable()->after('data_solicitacao');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'prazo_limite')) {
                $table->date('prazo_limite')->nullable()->after('data_visita_prevista');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'status')) {
                $table->string('status', 50)->nullable()->after('prazo_limite');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'responsavel')) {
                $table->string('responsavel', 150)->nullable()->after('status');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'relatorio')) {
                $table->text('relatorio')->nullable()->after('responsavel');
            }

            if (! Schema::hasColumn('visita_tecnicas', 'observacao')) {
                $table->text('observacao')->nullable()->after('relatorio');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('visita_tecnicas')) {
            return;
        }

        Schema::table('visita_tecnicas', function (Blueprint $table) {
            $colunas = [
                'unidade',
                'eixo',
                'processo_sei',
                'data_solicitacao',
                'data_visita_prevista',
                'prazo_limite',
                'status',
                'responsavel',
                'relatorio',
                'observacao',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('visita_tecnicas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
