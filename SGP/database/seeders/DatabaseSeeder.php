<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            CursoSeeder::class,
            PlanoDeMetaSeeder::class,
            PcaSeeder::class,
            CursoPorEixoSeeder::class,
            VisitaTecnicaSeeder::class,
            HoraPedagogicaSeeder::class,
            AcaoExtensivaSeeder::class,
            EventoSeeder::class,
        ]);
    }
}
