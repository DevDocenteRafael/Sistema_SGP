<?php

use App\Support\ConciliadorCursoOferta;

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::transaction(fn () => ConciliadorCursoOferta::vincularOfertasPendentes());
    }

    public function down(): void
    {
        // Não desfaz vínculos: a conciliação só preenche curso_id nulo.
    }
};
