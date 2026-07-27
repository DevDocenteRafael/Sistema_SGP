<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_por_eixos', function (Blueprint $table) {
            $table->id();
            $table->string('curso', 255);
            $table->string('eixo', 150);
            $table->string('unidade', 100)->nullable();
            $table->string('ano', 4)->nullable();
            $table->string('ch', 50)->nullable();
            $table->string('turmas', 20)->nullable();
            $table->string('codigo', 100)->nullable();
            $table->string('alunos', 20)->nullable();
            $table->string('instrutores', 255)->nullable();
            $table->string('status', 50)->default('Ativo');
            $table->text('observacao')->nullable();
            $table->boolean('is_novo')->default(false);
            $table->timestamps();

            $table->index(['ano', 'eixo']);
            $table->index('unidade');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_por_eixos');
    }
};
