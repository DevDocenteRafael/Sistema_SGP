<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapeamento = [
            'SENAC Asa Norte' => 'Asa Norte',
            'SENAC Asa Sul' => 'Asa Sul',
            'SENAC Taguatinga' => 'Taguatinga',
            'SENAC Ceilândia' => 'Ceilândia',
            'SENAC Gama' => 'Gama',
            'SENAC Sobradinho' => 'Sobradinho',
        ];

        foreach ($mapeamento as $antiga => $nova) {
            DB::table('usuarios')
                ->where('unidade', $antiga)
                ->update(['unidade' => $nova]);
        }
    }

    public function down(): void
    {
        //
    }
};
