<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolucoes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('curso_relacionado')->nullable();
            $table->string('categoria')->nullable();
            $table->text('resumo');
            $table->string('relator')->nullable();
            $table->string('setor')->nullable();
            $table->date('data_inicio_vigencia');
            $table->date('data_fim_vigencia')->nullable();
            $table->string('status')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('anexo_path')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('criado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('atualizado_por')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolucoes');
    }
};
