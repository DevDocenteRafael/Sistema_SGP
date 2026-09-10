<?php

namespace App\Services;

use App\Models\Ciclo;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class CicloContextoService
{
    public const HEADER = 'X-SIPED-Ciclo-Id';

    public function resolver(?Request $request = null, bool $permitirTodos = false): ?Ciclo
    {
        $request ??= request();
        if (! $request instanceof Request) {
            return Ciclo::atual();
        }

        $explicito = $request->input('ciclo_id');
        if ($explicito === 'todos') {
            if ($permitirTodos) {
                return null;
            }

            return Ciclo::atual();
        }

        if ($explicito !== null && $explicito !== '') {
            return $this->encontrarOuFalhar((int) $explicito);
        }

        $header = $request->headers->get(self::HEADER);
        if ($header !== null && $header !== '') {
            return $this->encontrarOuFalhar((int) $header);
        }

        return Ciclo::atual();
    }

    public function id(?Request $request = null, bool $permitirTodos = false): ?int
    {
        return $this->resolver($request, $permitirTodos)?->id;
    }

    /**
     * @return array{id: int, nome: string, atual: bool, anos: list<string>}|null
     */
    public function meta(?Request $request = null, bool $permitirTodos = false): ?array
    {
        return $this->resolver($request, $permitirTodos)?->paraMeta();
    }

    private function encontrarOuFalhar(int $id): Ciclo
    {
        $ciclo = Ciclo::query()->find($id);
        if (! $ciclo) {
            throw new HttpResponseException(response()->json([
                'message' => 'Ciclo de gestão não encontrado.',
            ], 422));
        }

        return $ciclo;
    }
}
