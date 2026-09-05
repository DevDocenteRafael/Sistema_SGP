<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return;
        }

        Schema::table('unidades_oferta', function (Blueprint $table) {
            $table->string('nome', 180)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return;
        }

        Schema::table('unidades_oferta', function (Blueprint $table) {
            $table->string('nome', 100)->change();
        });
    }
};
