<?php

namespace App\Models\Concerns;

use App\Models\Ciclo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PertenceAoCiclo
{
    public static function bootPertenceAoCiclo(): void
    {
        static::creating(function ($model) {
            if (empty($model->ciclo_id)) {
                $model->ciclo_id = app(\App\Services\CicloContextoService::class)->id()
                    ?? Ciclo::atual()?->id;
            }
        });
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function scopeDoCiclo($query, mixed $ciclo): void
    {
        Ciclo::aplicarFiltroNaConsulta($query, $ciclo instanceof Ciclo ? $ciclo->id : $ciclo);
    }
}
