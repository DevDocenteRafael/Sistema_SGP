<?php

namespace App\Models;

use App\Models\Concerns\ExclusaoLogica;
/**
 * Alias de compatibilidade. Use {@see CursoExecucao}.
 */
class CursoPorEixo extends CursoExecucao
{
    use ExclusaoLogica;

}
