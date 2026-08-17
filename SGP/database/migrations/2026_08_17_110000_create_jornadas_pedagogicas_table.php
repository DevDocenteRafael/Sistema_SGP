<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas_pedagogicas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('tem_pre_jornada', 3)->default('Não');
            $table->date('data_pre_jornada')->nullable();
            $table->string('local', 255)->nullable();
            $table->string('espaco', 255)->nullable();
            $table->string('verba', 100)->nullable();
            $table->text('custos')->nullable();
            $table->text('programacao')->nullable();
            $table->string('setores', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->string('status', 50)->default('Rascunho');
            $table->string('anexo_path')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('criado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('atualizado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas_pedagogicas');
    }
};
