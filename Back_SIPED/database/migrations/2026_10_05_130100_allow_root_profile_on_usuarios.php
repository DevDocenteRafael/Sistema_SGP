<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 03 — perfil Root. A coluna era ENUM(Administrador, Editor, Consultor);
 * passa a texto curto, validado pela aplicação (config/permissoes.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('perfil', 30)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('usuarios')->where('perfil', 'Root')->exists()) {
            throw new RuntimeException('Existem usuários Root. Altere o perfil deles antes de desfazer esta migration.');
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('perfil', ['Administrador', 'Editor', 'Consultor'])->change();
        });
    }
};
