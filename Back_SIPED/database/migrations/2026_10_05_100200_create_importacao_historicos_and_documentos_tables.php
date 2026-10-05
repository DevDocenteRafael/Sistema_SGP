<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Histórico auditável de cada importação (planilha ou documento PDF).
        Schema::create('importacao_historicos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->default('planilha'); // planilha | documento
            $table->string('modulo', 60);
            $table->string('modulo_label', 120)->nullable();
            $table->string('situacao', 20)->default('concluida'); // concluida | bloqueada
            $table->string('arquivo_nome', 255);
            $table->string('arquivo_path', 500)->nullable();
            $table->string('arquivo_hash', 64)->nullable();
            $table->unsignedBigInteger('arquivo_tamanho')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('ciclo_id')->nullable()->constrained('portfolio_ciclos')->nullOnDelete();
            $table->unsignedInteger('novos')->default(0);
            $table->unsignedInteger('atualizados')->default(0);
            $table->unsignedInteger('sem_alteracao')->default(0);
            $table->unsignedInteger('incompletos')->default(0);
            $table->unsignedInteger('ignorados')->default(0);
            $table->unsignedInteger('erros')->default(0);
            $table->text('mensagem')->nullable();
            $table->json('detalhes')->nullable();
            $table->timestamps();

            $table->index(['modulo', 'created_at']);
        });

        // Documentos PDF (ex.: oriundos do SEI) guardados como anexo controlado.
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 60);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('titulo', 255);
            $table->string('processo_sei', 100)->nullable();
            $table->text('descricao')->nullable();
            $table->string('arquivo_nome', 255);
            $table->string('arquivo_path', 500);
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamanho');
            $table->string('hash', 64);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('importacao_historico_id')->nullable()->constrained('importacao_historicos')->nullOnDelete();
            $table->timestamps();

            $table->index(['modulo', 'registro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('importacao_historicos');
    }
};
