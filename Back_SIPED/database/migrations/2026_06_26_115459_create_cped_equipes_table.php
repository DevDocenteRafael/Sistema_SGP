<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cped_equipes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('cargo', 100);
            $table->string('setor', 100)->nullable();
            $table->string('contato', 100)->nullable();
            $table->string('tipo', 50);
            $table->string('eixo_vinculado', 100)->nullable();
            $table->string('iniciais', 20)->nullable();
            $table->text('foto')->nullable();
            $table->string('cor', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cped_equipes');
    }
};
