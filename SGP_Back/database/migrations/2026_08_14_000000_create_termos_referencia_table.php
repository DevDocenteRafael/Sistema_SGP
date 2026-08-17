<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termos_referencia', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('eixo', 150)->nullable();
            $table->string('processo_sei', 50);
            $table->date('prazo_deadline');
            $table->string('status', 50)->default('Planejamento');
            $table->text('observacao')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->dateTime('concluido_em')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('criado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('atualizado_por')->references('id')->on('usuarios')->nullOnDelete();

            $table->index(['status']);
            $table->index(['prazo_deadline']);
            $table->index(['eixo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termos_referencia');
    }
};
