<?php

use App\Support\CatalogoInstitucional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('eixos')) {
            Schema::create('eixos', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 150);
                $table->string('slug', 160)->unique();
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('segmentos')) {
            Schema::create('segmentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('eixo_id')->constrained('eixos')->cascadeOnDelete();
                $table->string('nome', 150);
                $table->string('slug', 160)->unique();
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();

                $table->index('eixo_id');
            });
        }

        CatalogoInstitucional::sincronizar();
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentos');
        Schema::dropIfExists('eixos');
    }
};
