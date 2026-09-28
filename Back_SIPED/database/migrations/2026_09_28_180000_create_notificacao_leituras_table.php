<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacao_leituras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('chave', 120);
            $table->timestamp('lida_em');
            $table->timestamps();

            $table->unique(['usuario_id', 'chave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacao_leituras');
    }
};
