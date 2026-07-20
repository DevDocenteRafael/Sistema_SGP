<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_de_metas', function (Blueprint $table) {
            $table->id();
            $table->string('segmento', 100)->nullable();
            $table->string('curso', 255)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->string('numero_sei', 100)->nullable();
            $table->string('codigo_sig', 100)->nullable();
            $table->string('mes_entrega', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('origem', 100)->nullable();
            $table->string('status_final', 50)->nullable();
            $table->text('observacao')->nullable();
            $table->integer('ano')->nullable();
            $table->timestamps();

            $table->unique('numero_sei');
            $table->unique('codigo_sig');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_de_metas');
    }
};
