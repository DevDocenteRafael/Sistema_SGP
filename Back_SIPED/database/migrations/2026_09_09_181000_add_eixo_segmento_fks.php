<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cursos')) {
            Schema::table('cursos', function (Blueprint $table) {
                if (! Schema::hasColumn('cursos', 'segmento')) {
                    $table->string('segmento', 150)->nullable();
                }
            });

            Schema::table('cursos', function (Blueprint $table) {
                if (! Schema::hasColumn('cursos', 'eixo_id')) {
                    $table->foreignId('eixo_id')->nullable()->constrained('eixos')->nullOnDelete();
                }
                if (! Schema::hasColumn('cursos', 'segmento_id')) {
                    $table->foreignId('segmento_id')->nullable()->constrained('segmentos')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('curso_por_eixos')) {
            Schema::table('curso_por_eixos', function (Blueprint $table) {
                if (! Schema::hasColumn('curso_por_eixos', 'segmento')) {
                    $table->string('segmento', 150)->nullable();
                }
            });

            Schema::table('curso_por_eixos', function (Blueprint $table) {
                if (! Schema::hasColumn('curso_por_eixos', 'eixo_id')) {
                    $table->foreignId('eixo_id')->nullable()->constrained('eixos')->nullOnDelete();
                }
                if (! Schema::hasColumn('curso_por_eixos', 'segmento_id')) {
                    $table->foreignId('segmento_id')->nullable()->constrained('segmentos')->nullOnDelete();
                }
                if (! Schema::hasColumn('curso_por_eixos', 'curso_id')) {
                    $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'eixo_id')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('eixo_id');
                $table->dropConstrainedForeignId('segmento_id');
                if (Schema::hasColumn('cursos', 'segmento')) {
                    $table->dropColumn('segmento');
                }
            });
        }

        if (Schema::hasTable('curso_por_eixos') && Schema::hasColumn('curso_por_eixos', 'eixo_id')) {
            Schema::table('curso_por_eixos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('eixo_id');
                $table->dropConstrainedForeignId('segmento_id');
                $table->dropConstrainedForeignId('curso_id');
                if (Schema::hasColumn('curso_por_eixos', 'segmento')) {
                    $table->dropColumn('segmento');
                }
            });
        }
    }
};
