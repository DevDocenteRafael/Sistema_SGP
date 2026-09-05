<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return;
        }

        Schema::table('unidades_oferta', function (Blueprint $table) {
            if (! Schema::hasColumn('unidades_oferta', 'codigo')) {
                $table->string('codigo', 50)->nullable()->after('tipo');
            }
            if (! Schema::hasColumn('unidades_oferta', 'endereco')) {
                $table->string('endereco', 255)->nullable()->after('codigo');
            }
            if (! Schema::hasColumn('unidades_oferta', 'responsavel')) {
                $table->string('responsavel', 150)->nullable()->after('endereco');
            }
        });

        // cep (legado CEP) passa a ser o tipo "unidade" do conceito Estruturas Institucionais
        DB::table('unidades_oferta')
            ->where('tipo', 'cep')
            ->update(['tipo' => 'unidade']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return;
        }

        DB::table('unidades_oferta')
            ->where('tipo', 'unidade')
            ->update(['tipo' => 'cep']);

        Schema::table('unidades_oferta', function (Blueprint $table) {
            if (Schema::hasColumn('unidades_oferta', 'responsavel')) {
                $table->dropColumn('responsavel');
            }
            if (Schema::hasColumn('unidades_oferta', 'endereco')) {
                $table->dropColumn('endereco');
            }
            if (Schema::hasColumn('unidades_oferta', 'codigo')) {
                $table->dropColumn('codigo');
            }
        });
    }
};
