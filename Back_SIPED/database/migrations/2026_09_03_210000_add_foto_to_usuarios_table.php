<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuarios') || Schema::hasColumn('usuarios', 'foto')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('foto', 255)->nullable()->after('telefone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('usuarios') || ! Schema::hasColumn('usuarios', 'foto')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
