<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException(
                'Seeders de demonstração bloqueados em production. '
                .'Defina ALLOW_DEMO_SEED=true apenas se tiver certeza (e troque as senhas depois).'
            );
        }

        if ($this->command) {
            $this->command->warn('Seeders criam usuários/dados de demonstração. Não use senhas padrão fora do ambiente local.');
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
