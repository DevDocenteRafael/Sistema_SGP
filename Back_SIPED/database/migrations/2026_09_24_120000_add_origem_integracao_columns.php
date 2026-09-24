<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('origem_dados.tabelas_externas', []) as $tabela) {
            if (! Schema::hasTable($tabela) || Schema::hasColumn($tabela, 'source_type')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->string('source_type', 32)->default('seeder')->index();
                $table->string('source_system', 80)->nullable()->index();
                $table->string('external_id', 120)->nullable()->index();
                $table->timestamp('synced_at')->nullable();
            });
        }

        if (! Schema::hasTable('sincronizacao_historicos')) {
            Schema::create('sincronizacao_historicos', function (Blueprint $table) {
                $table->id();
                $table->string('sistema', 80);
                $table->string('entidade', 80)->nullable();
                $table->timestamp('iniciado_em')->nullable();
                $table->timestamp('finalizado_em')->nullable();
                $table->unsignedInteger('recebidos')->default(0);
                $table->unsignedInteger('criados')->default(0);
                $table->unsignedInteger('atualizados')->default(0);
                $table->unsignedInteger('ignorados')->default(0);
                $table->unsignedInteger('erros')->default(0);
                $table->string('status', 40)->default('pendente');
                $table->text('observacao')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacao_historicos');

        foreach (config('origem_dados.tabelas_externas', []) as $tabela) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'source_type')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn(['source_type', 'source_system', 'external_id', 'synced_at']);
            });
        }
    }
};
