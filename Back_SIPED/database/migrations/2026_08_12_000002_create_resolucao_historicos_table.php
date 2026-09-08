<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolucao_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolucao_id')->constrained('resolucoes')->cascadeOnDelete();
            $table->string('evento');
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('observacao')->nullable();
            $table->text('justificativa')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolucao_historicos');
    }
};
