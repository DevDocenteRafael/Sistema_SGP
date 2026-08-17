<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_ciclos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->foreignId('origem_id')->nullable()->constrained('portfolio_ciclos')->nullOnDelete();
            $table->boolean('atual')->default(false);
            $table->string('observacao', 255)->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('criado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('atualizado_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        $agora = now();
        $cicloId = DB::table('portfolio_ciclos')->insertGetId([
            'nome' => '2025-2026',
            'atual' => true,
            'observacao' => 'Ciclo padrão criado para os cursos já cadastrados.',
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        Schema::table('cursos', function (Blueprint $table) {
            $table->unsignedBigInteger('ciclo_id')->nullable()->after('id');
            $table->text('justificativa_duplicidade')->nullable()->after('pcr');

            $table->foreign('ciclo_id')->references('id')->on('portfolio_ciclos')->nullOnDelete();
            $table->index('ciclo_id');
        });

        if (Schema::hasTable('cursos')) {
            DB::table('cursos')->whereNull('ciclo_id')->update(['ciclo_id' => $cicloId]);
        }
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
            $table->dropColumn(['ciclo_id', 'justificativa_duplicidade']);
        });

        Schema::dropIfExists('portfolio_ciclos');
    }
};
