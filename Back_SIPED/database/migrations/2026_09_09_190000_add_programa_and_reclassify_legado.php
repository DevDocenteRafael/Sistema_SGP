<?php

use App\Support\ClassificadorLegado;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cursos') && ! Schema::hasColumn('cursos', 'programa')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->string('programa', 80)->nullable();
            });
        }

        if (Schema::hasTable('curso_por_eixos') && ! Schema::hasColumn('curso_por_eixos', 'programa')) {
            Schema::table('curso_por_eixos', function (Blueprint $table) {
                $table->string('programa', 80)->nullable();
            });
        }

        ClassificadorLegado::reclassificarTabelas();
    }

    public function down(): void
    {
        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'programa')) {
            Schema::table('cursos', function (Blueprint $table) {
                $table->dropColumn('programa');
            });
        }

        if (Schema::hasTable('curso_por_eixos') && Schema::hasColumn('curso_por_eixos', 'programa')) {
            Schema::table('curso_por_eixos', function (Blueprint $table) {
                $table->dropColumn('programa');
            });
        }
    }
};
