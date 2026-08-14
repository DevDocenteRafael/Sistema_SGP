<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termos_referencia_historicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('termo_referencia_id');
            $table->string('acao', 255);
            $table->string('tipo', 50)->default('info');
            $table->string('situacao_anterior', 50)->nullable();
            $table->string('situacao_nova', 50)->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('termo_referencia_id')->references('id')->on('termos_referencia')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();

            $table->index(['termo_referencia_id']);
            $table->index(['created_at']);
            $table->index(['tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termos_referencia_historicos');
    }
};
