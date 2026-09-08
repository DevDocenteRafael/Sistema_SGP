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
            if (! Schema::hasColumn('unidades_oferta', 'motivo_inativacao')) {
                $table->text('motivo_inativacao')->nullable()->after('ativo');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return;
        }

        Schema::table('unidades_oferta', function (Blueprint $table) {
            if (Schema::hasColumn('unidades_oferta', 'motivo_inativacao')) {
                $table->dropColumn('motivo_inativacao');
            }
        });
    }
};
