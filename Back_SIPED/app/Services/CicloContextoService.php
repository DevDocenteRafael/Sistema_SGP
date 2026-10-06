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

        // Cabeçalho é o ciclo lembrado pelo navegador: se ele foi excluído,
        // volta para o ciclo atual em vez de travar todas as telas.
        $header = $request->headers->get(self::HEADER);
        if ($header !== null && $header !== '') {
            return Ciclo::query()->find((int) $header) ?? Ciclo::atual();
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
        // Ciclo na lixeira (ainda lembrado por algum navegador): volta para o ciclo atual.
        if (! $ciclo && Ciclo::onlyTrashed()->whereKey($id)->exists()) {
            $ciclo = Ciclo::atual();
        }
        if (! $ciclo) {
            throw new HttpResponseException(response()->json([
                'message' => 'Ciclo de gestão não encontrado.',
            ], 422));
        }

        return $ciclo;
    }
}
