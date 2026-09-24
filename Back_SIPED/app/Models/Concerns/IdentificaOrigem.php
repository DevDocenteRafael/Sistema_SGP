<?php

namespace App\Models\Concerns;

trait IdentificaOrigem
{
    public function initializeIdentificaOrigem(): void
    {
        $this->mergeFillable([
            'source_type',
            'source_system',
            'external_id',
            'synced_at',
        ]);
        $this->mergeCasts([
            'synced_at' => 'datetime',
        ]);
        $this->mergeHidden([]);
    }

    public static function bootIdentificaOrigem(): void
    {
        static::creating(function ($model) {
            if (empty($model->source_type)) {
                $model->source_type = 'seeder';
            }
        });
    }

    public function origemResumo(): array
    {
        return [
            'source_type' => $this->source_type,
            'source_system' => $this->source_system,
            'external_id' => $this->external_id,
            'synced_at' => $this->synced_at?->toIso8601String(),
        ];
    }
}
