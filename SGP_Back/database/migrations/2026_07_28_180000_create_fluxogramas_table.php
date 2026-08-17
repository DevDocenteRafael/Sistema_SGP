<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluxogramas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 100);
            $table->string('slug', 50)->unique();
            $table->text('descricao')->nullable();
            $table->string('tipo', 20)->default('linear');
            $table->json('diagrama');
            $table->boolean('ativo')->default(true);
            $table->foreignId('criado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fluxogramas');
    }
};
