<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complementa eventos com colunas do protótipo / modelo físico.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('eventos')) {
            return;
        }

        Schema::table('eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('eventos', 'nome')) {
                $table->string('nome', 200)->nullable()->after('id');
            }

            if (! Schema::hasColumn('eventos', 'ano')) {
                $table->string('ano', 4)->nullable()->after('nome');
            }

            if (! Schema::hasColumn('eventos', 'data')) {
                $table->date('data')->nullable()->after('ano');
            }

            if (! Schema::hasColumn('eventos', 'unidade')) {
                $table->string('unidade', 100)->nullable()->after('data');
            }

            if (! Schema::hasColumn('eventos', 'eixo')) {
                $table->string('eixo', 150)->nullable()->after('unidade');
            }

            if (! Schema::hasColumn('eventos', 'quantidade_pessoas')) {
                $table->unsignedInteger('quantidade_pessoas')->nullable()->after('eixo');
            }

            if (! Schema::hasColumn('eventos', 'equipe')) {
                $table->string('equipe', 255)->nullable()->after('quantidade_pessoas');
            }

            if (! Schema::hasColumn('eventos', 'possui_acao_extensiva')) {
                $table->string('possui_acao_extensiva', 3)->nullable()->after('equipe');
            }

            if (! Schema::hasColumn('eventos', 'acao_vinculada')) {
                $table->string('acao_vinculada', 255)->nullable()->after('possui_acao_extensiva');
            }

            if (! Schema::hasColumn('eventos', 'status')) {
                $table->string('status', 50)->nullable()->after('acao_vinculada');
            }

            if (! Schema::hasColumn('eventos', 'observacao')) {
                $table->text('observacao')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('eventos')) {
            return;
        }

        Schema::table('eventos', function (Blueprint $table) {
            $colunas = [
                'nome',
                'ano',
                'data',
                'unidade',
                'eixo',
                'quantidade_pessoas',
                'equipe',
                'possui_acao_extensiva',
                'acao_vinculada',
                'status',
                'observacao',
            ];

            $existentes = array_values(array_filter(
                $colunas,
                fn (string $coluna) => Schema::hasColumn('eventos', $coluna)
            ));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};
