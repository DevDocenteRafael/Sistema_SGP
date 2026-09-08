<?php

namespace Database\Seeders\Concerns;

use App\Models\PortfolioCiclo;

trait UsaCicloPortfolio
{
    protected function cicloIdAtual(): ?int
    {
        return PortfolioCiclo::atual()?->id;
    }

    /**
     * @param  array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    protected function comCiclo(array $registro): array
    {
        $cicloId = $this->cicloIdAtual();

        if ($cicloId) {
            $registro['ciclo_id'] = $cicloId;
        }

        return $registro;
    }
}
