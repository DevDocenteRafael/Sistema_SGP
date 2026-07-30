<?php

namespace Database\Seeders;

use App\Models\Fluxograma;
use App\Services\FluxogramaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FluxogramaSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(FluxogramaService::class);

        Fluxograma::query()->updateOrCreate(
            ['slug' => 'exemplo-linear'],
            [
                'titulo' => 'Exemplo linear',
                'descricao' => 'Modelo simples (início → processo → fim) para começar um fluxograma.',
                'tipo' => Fluxograma::TIPO_LINEAR,
                'diagrama' => $service->diagramaPadrao(Fluxograma::TIPO_LINEAR),
                'ativo' => true,
            ]
        );

        $caminhoVisita = database_path('data/fluxogramas/visita-tecnica-cped.json');
        $diagramaVisita = File::exists($caminhoVisita)
            ? json_decode(File::get($caminhoVisita), true)
            : null;

        if (! is_array($diagramaVisita)) {
            $diagramaVisita = $service->diagramaPadrao(Fluxograma::TIPO_FUNCIONAL);
        }

        Fluxograma::query()->updateOrCreate(
            ['slug' => 'visita-tecnica-cped'],
            [
                'titulo' => 'Visita técnica CPED',
                'descricao' => 'Fluxo funcional com raias (Unidade, CPED e Docente/Coordenação).',
                'tipo' => Fluxograma::TIPO_FUNCIONAL,
                'diagrama' => $diagramaVisita,
                'ativo' => true,
            ]
        );
    }
}
