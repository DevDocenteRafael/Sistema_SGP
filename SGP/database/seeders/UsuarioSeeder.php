<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => 'administrador@df.senac.br'],
            [
                'nome' => 'Administrador',
                'senha' => Hash::make('senac2025'),
                'perfil' => 'Administrador',
                'status' => true,
                'unidade' => 'SENAC DF',
            ]
        );
    }
}
