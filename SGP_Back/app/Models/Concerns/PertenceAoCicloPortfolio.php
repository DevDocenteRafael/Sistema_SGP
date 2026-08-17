<?php

namespace App\Models\Concerns;

use App\Models\PortfolioCiclo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PertenceAoCicloPortfolio
{
    public static function bootPertenceAoCicloPortfolio(): void
    {
        static::creating(function ($model) {
            if (empty($model->ciclo_id)) {
                $model->ciclo_id = PortfolioCiclo::atual()?->id;
            }
        });
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PortfolioCiclo::class, 'ciclo_id');
    }
}
