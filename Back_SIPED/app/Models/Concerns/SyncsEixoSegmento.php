<?php

namespace App\Models\Concerns;

use App\Support\CatalogoInstitucional;
use App\Support\CatalogoOficial;

trait SyncsEixoSegmento
{
    public static function bootSyncsEixoSegmento(): void
    {
        static::saving(function ($model) {
            $resolvido = CatalogoOficial::resolverEixoESegmento(
                $model->eixo ?? null,
                $model->segmento ?? null,
            );

            if ($resolvido['erro'] !== null && $resolvido['eixo'] === null) {
                if (in_array('programa', $model->getFillable(), true) && ($resolvido['programa'] ?? null)) {
                    $model->programa = $resolvido['programa'];
                }

                return;
            }

            if ($resolvido['eixo'] !== null) {
                $model->eixo = $resolvido['eixo'];
            }

            if (in_array('segmento', $model->getFillable(), true)) {
                $model->segmento = $resolvido['segmento'];
            }

            if (in_array('programa', $model->getFillable(), true) && ($resolvido['programa'] ?? null)) {
                $model->programa = $resolvido['programa'];
            }

            $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
            $model->eixo_id = $ids['eixo_id'];
            $model->segmento_id = $ids['segmento_id'];
        });
    }
}
