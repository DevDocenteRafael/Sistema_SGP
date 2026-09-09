<?php

namespace Database\Seeders;

use App\Support\CatalogoInstitucional;
use Illuminate\Database\Seeder;

class EixoSeeder extends Seeder
{
    public function run(): void
    {
        CatalogoInstitucional::sincronizar();
    }
}
