<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pcas', function (Blueprint $table) {
            $table->string('responsavel', 255)->nullable()->after('status');
            $table->text('objetivo')->nullable()->after('responsavel');
            $table->date('data_inicio')->nullable()->after('objetivo');
            $table->date('data_fim')->nullable()->after('data_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcas', function (Blueprint $table) {
            $table->dropColumn(['responsavel', 'objetivo', 'data_inicio', 'data_fim']);
        });
    }
};
