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
            $table->string('segmento', 255)->nullable();
            $table->string('curso', 500)->nullable();
            $table->string('tipo', 150)->nullable();
            $table->string('numero_sei', 100)->nullable();
            $table->string('codigo_sig', 100)->nullable();
            $table->string('mes_entrega', 100)->nullable();
            $table->text('status')->nullable();
            $table->text('origem')->nullable();
            $table->text('status_final')->nullable();
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
