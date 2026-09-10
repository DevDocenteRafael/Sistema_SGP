<?php

use App\Support\BackfillCicloOperacional;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        foreach (array_keys(BackfillCicloOperacional::tabelas()) as $tabela) {
            if (! Schema::hasTable($tabela) || Schema::hasColumn($tabela, 'ciclo_id')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->foreignId('ciclo_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('portfolio_ciclos')
                    ->nullOnDelete();
                $table->index('ciclo_id');
            });
        }

        BackfillCicloOperacional::executar();
    }

    public function down(): void
    {
        foreach (array_keys(BackfillCicloOperacional::tabelas()) as $tabela) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'ciclo_id')) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) {
                $table->dropConstrainedForeignId('ciclo_id');
            });
        }
    }
};
