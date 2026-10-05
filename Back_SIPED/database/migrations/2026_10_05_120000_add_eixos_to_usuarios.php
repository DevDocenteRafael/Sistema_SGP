<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eixos sob responsabilidade do usuário: define o escopo das notificações internas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'eixos')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->json('eixos')->nullable()->after('area');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'eixos')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('eixos');
            });
        }
    }
};
