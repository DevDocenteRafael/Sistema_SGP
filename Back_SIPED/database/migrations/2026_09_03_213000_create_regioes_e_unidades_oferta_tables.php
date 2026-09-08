<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regioes_administrativas')) {
            Schema::create('regioes_administrativas', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100)->unique();
                $table->boolean('ativo')->default(true);
                $table->foreignId('criado_por')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->foreignId('atualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('unidades_oferta')) {
            Schema::create('unidades_oferta', function (Blueprint $table) {
                $table->id();
                $table->foreignId('regiao_administrativa_id')
                    ->constrained('regioes_administrativas')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->string('nome', 100);
                $table->string('tipo', 20);
                $table->boolean('ativo')->default(true);
                $table->foreignId('criado_por')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->foreignId('atualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->timestamps();

                $table->unique(['regiao_administrativa_id', 'nome'], 'unidades_oferta_ra_nome_unique');
                $table->index(['ativo', 'tipo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_oferta');
        Schema::dropIfExists('regioes_administrativas');
    }
};
