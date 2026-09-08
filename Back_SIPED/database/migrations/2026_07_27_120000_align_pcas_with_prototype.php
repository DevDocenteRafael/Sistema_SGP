<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinha a tabela pcas ao protótipo (valores / precificação de cursos).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pcas')) {
            return;
        }

        Schema::table('pcas', function (Blueprint $table) {
            if (! Schema::hasColumn('pcas', 'titulo')) {
                $table->string('titulo', 255)->nullable()->after('id');
            }

            if (! Schema::hasColumn('pcas', 'semestre')) {
                $table->string('semestre', 20)->nullable()->after('titulo');
            }

            if (! Schema::hasColumn('pcas', 'eixo')) {
                $table->string('eixo', 100)->nullable()->after('semestre');
            }

            if (! Schema::hasColumn('pcas', 'carga_horaria')) {
                $table->string('carga_horaria', 50)->nullable()->after('unidade');
            }

            if (! Schema::hasColumn('pcas', 'precificacao')) {
                $table->string('precificacao', 100)->nullable()->after('carga_horaria');
            }

            if (! Schema::hasColumn('pcas', 'valor_primeiro_modulo')) {
                $table->string('valor_primeiro_modulo', 50)->nullable()->after('precificacao');
            }

            if (! Schema::hasColumn('pcas', 'valor')) {
                $table->string('valor', 50)->nullable()->after('valor_primeiro_modulo');
            }

            if (! Schema::hasColumn('pcas', 'parcelas_boleto')) {
                $table->string('parcelas_boleto', 50)->nullable()->after('valor');
            }

            if (! Schema::hasColumn('pcas', 'valor_parcela_boleto')) {
                $table->string('valor_parcela_boleto', 50)->nullable()->after('parcelas_boleto');
            }

            if (! Schema::hasColumn('pcas', 'parcelas_cartao')) {
                $table->string('parcelas_cartao', 50)->nullable()->after('valor_parcela_boleto');
            }

            if (! Schema::hasColumn('pcas', 'valor_cartao')) {
                $table->string('valor_cartao', 50)->nullable()->after('parcelas_cartao');
            }

            if (! Schema::hasColumn('pcas', 'parcela_desc_20')) {
                $table->string('parcela_desc_20', 50)->nullable()->after('valor_cartao');
            }

            if (! Schema::hasColumn('pcas', 'parcela_desc_15')) {
                $table->string('parcela_desc_15', 50)->nullable()->after('parcela_desc_20');
            }
        });

        if (Schema::hasColumn('pcas', 'curso') && Schema::hasColumn('pcas', 'titulo')) {
            DB::table('pcas')
                ->where(function ($query) {
                    $query->whereNull('titulo')->orWhere('titulo', '');
                })
                ->update(['titulo' => DB::raw('curso')]);
        }

        if (Schema::hasColumn('pcas', 'periodo') && Schema::hasColumn('pcas', 'semestre')) {
            DB::table('pcas')
                ->where(function ($query) {
                    $query->whereNull('semestre')->orWhere('semestre', '');
                })
                ->update(['semestre' => DB::raw('periodo')]);
        }

        Schema::table('pcas', function (Blueprint $table) {
            $obsoletas = [
                'curso',
                'tipo',
                'periodo',
                'responsavel',
                'objetivo',
                'data_inicio',
                'data_fim',
            ];

            $existentes = array_values(array_filter(
                $obsoletas,
                fn (string $coluna) => Schema::hasColumn('pcas', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pcas')) {
            return;
        }

        Schema::table('pcas', function (Blueprint $table) {
            if (! Schema::hasColumn('pcas', 'curso')) {
                $table->string('curso', 255)->nullable();
            }

            if (! Schema::hasColumn('pcas', 'tipo')) {
                $table->string('tipo', 100)->nullable();
            }

            if (! Schema::hasColumn('pcas', 'periodo')) {
                $table->string('periodo', 100)->nullable();
            }

            if (! Schema::hasColumn('pcas', 'responsavel')) {
                $table->string('responsavel', 255)->nullable();
            }

            if (! Schema::hasColumn('pcas', 'objetivo')) {
                $table->text('objetivo')->nullable();
            }

            if (! Schema::hasColumn('pcas', 'data_inicio')) {
                $table->date('data_inicio')->nullable();
            }

            if (! Schema::hasColumn('pcas', 'data_fim')) {
                $table->date('data_fim')->nullable();
            }
        });

        Schema::table('pcas', function (Blueprint $table) {
            $colunas = [
                'titulo',
                'semestre',
                'eixo',
                'carga_horaria',
                'precificacao',
                'valor_primeiro_modulo',
                'valor',
                'parcelas_boleto',
                'valor_parcela_boleto',
                'parcelas_cartao',
                'valor_cartao',
                'parcela_desc_20',
                'parcela_desc_15',
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
