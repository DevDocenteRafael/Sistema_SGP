<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_quadros', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('slug', 50)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('kanban_colunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kanban_quadro_id')
                ->constrained('kanban_quadros')
                ->cascadeOnDelete();
            $table->string('titulo', 80);
            $table->unsignedInteger('position')->default(0);
            $table->string('cor', 20)->nullable();
            $table->timestamps();

            $table->index(['kanban_quadro_id', 'position']);
        });

        Schema::create('kanban_cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kanban_coluna_id')
                ->constrained('kanban_colunas')
                ->cascadeOnDelete();
            $table->string('titulo', 150);
            $table->text('descricao')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('criado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['kanban_coluna_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_cartoes');
        Schema::dropIfExists('kanban_colunas');
        Schema::dropIfExists('kanban_quadros');
    }
};
