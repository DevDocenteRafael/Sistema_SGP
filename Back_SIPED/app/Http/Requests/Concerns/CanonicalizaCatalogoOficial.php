<?php

namespace App\Http\Requests\Concerns;

use App\Support\CatalogoOficial;

trait CanonicalizaCatalogoOficial
{
    protected function canonicalizarEixoInput(string $campo = 'eixo'): void
    {
        if (! $this->filled($campo)) {
            return;
        }

        $bruto = (string) $this->input($campo);
        $segCanon = CatalogoOficial::canonicalizarSegmento($bruto);
        if ($segCanon !== null && ! $this->filled('segmento')) {
            $this->merge(['segmento' => $segCanon]);
        }

        $canon = CatalogoOficial::canonicalizarEixo($bruto);
        if ($canon !== null) {
            $this->merge([$campo => $canon]);
        }
    }

    protected function canonicalizarProgramaInput(string $campo = 'programa'): void
    {
        if (! $this->filled($campo)) {
            return;
        }

        $canon = CatalogoOficial::canonicalizarPrograma((string) $this->input($campo));
        if ($canon !== null) {
            $this->merge([$campo => $canon]);
        }
    }

    protected function canonicalizarModalidadeInput(string $campo = 'modalidade'): void
    {
        if (! $this->filled($campo)) {
            return;
        }

        $canon = CatalogoOficial::canonicalizarModalidade((string) $this->input($campo));
        if ($canon !== null) {
            $this->merge([$campo => $canon]);
        }
    }

    protected function canonicalizarSegmentoInput(string $campo = 'segmento'): void
    {
        if (! $this->exists($campo)) {
            return;
        }

        if ($this->input($campo) === '' || $this->input($campo) === null) {
            $this->merge([$campo => null]);

            return;
        }

        $canon = CatalogoOficial::canonicalizarSegmento((string) $this->input($campo));
        if ($canon !== null) {
            $this->merge([$campo => $canon]);
        }
    }
}
