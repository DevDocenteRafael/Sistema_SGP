<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('eixo', 150)->nullable();
            $table->string('modalidade', 100)->nullable();
            $table->string('carga_horaria', 50)->nullable();
            $table->string('turmas', 20)->nullable();
            $table->string('codigo_processo', 100)->nullable();
            $table->string('alunos', 20)->nullable();
            $table->string('instrutor', 255)->nullable();
            $table->text('descricao')->nullable();
            $table->string('codigo_dn', 100)->nullable();
            $table->string('codigo_sig', 100)->nullable();
            $table->string('identificacao', 50)->nullable();
            $table->string('tipo', 150)->nullable();
            $table->string('status', 50)->default('ATIVO');
            $table->string('ultima_revisao', 50)->nullable();
            $table->string('processo_sei', 100)->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->text('unidade')->nullable();
            $table->json('unidades_oferta')->nullable();
            $table->text('observacoes')->nullable();
            $table->text('valores')->nullable();
            $table->string('compativel_bolsa', 10)->nullable();
            $table->string('comercial', 10)->nullable();
            $table->string('pcn', 255)->nullable();
            $table->string('pcr', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
