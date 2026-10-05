<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('termos_referencia', function (Blueprint $table) {
            if (! Schema::hasColumn('termos_referencia', 'numero_tr')) {
                $table->string('numero_tr', 50)->nullable()->after('nome');
            }
            if (! Schema::hasColumn('termos_referencia', 'numero_ata')) {
                $table->string('numero_ata', 100)->nullable()->after('processo_sei');
                $table->index('numero_ata', 'termos_referencia_numero_ata_index');
            }
            if (! Schema::hasColumn('termos_referencia', 'data_vencimento_ata')) {
                $table->date('data_vencimento_ata')->nullable()->after('numero_ata');
            }
            if (! Schema::hasColumn('termos_referencia', 'ata_renovada')) {
                $table->boolean('ata_renovada')->default(false)->after('data_vencimento_ata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('termos_referencia', function (Blueprint $table) {
            if (Schema::hasColumn('termos_referencia', 'numero_ata')) {
                $table->dropIndex('termos_referencia_numero_ata_index');
            }
            foreach (['numero_tr', 'numero_ata', 'data_vencimento_ata', 'ata_renovada'] as $coluna) {
                if (Schema::hasColumn('termos_referencia', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
