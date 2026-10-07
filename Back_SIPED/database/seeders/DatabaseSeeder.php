<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SomenteForaDeProducao;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use SomenteForaDeProducao;

    /**
     * Ambiente de desenvolvimento/homologação completo: catálogo oficial, ciclos, unidades,
     * usuários demo e massa de dados de todos os módulos. Em produção, nada daqui roda
     * (Root real: php artisan siped:criar-root).
     */
    public function run(): void
    {
        $this->bloquearEmProducao();

        if ($this->command) {
            $this->command->warn('Seeders de demonstração: apagam os dados operacionais e recriam a massa de teste.');
        }

        $this->call([
            TruncarDadosOperacionaisSeeder::class,
            EixoSeeder::class,
            CicloSeeder::class,
            UnidadeOfertaSeeder::class,
            UsuarioSeeder::class,
            CpedEquipeSeeder::class,
            MassaDadosSeeder::class,
            KanbanSeeder::class,
            FluxogramaSeeder::class,
        ]);
    }
}
