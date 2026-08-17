<?php

namespace App\Services;

use App\Models\Curso;
use Illuminate\Support\Collection;

class CursoDuplicidadeService
{
    /**
     * @param  array<string, mixed>  $dados
     * @return Collection<int, Curso>
     */
    public function buscarSimilares(array $dados, ?int $excetoId = null, ?int $cicloId = null): Collection
    {
        $titulo = $this->normalizar($dados['titulo'] ?? null);
        $sig = $this->normalizar($dados['codigo_sig'] ?? null);
        $sei = $this->normalizar($dados['processo_sei'] ?? null);

        if ($titulo === '' && $sig === '' && $sei === '') {
            return collect();
        }

        $query = Curso::query()->orderBy('id');

        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }

        if ($excetoId) {
            $query->where('id', '!=', $excetoId);
        }

        $query->where(function ($q) use ($titulo, $sig, $sei) {
            if ($titulo !== '') {
                $q->orWhereRaw('LOWER(titulo) = ?', [$titulo]);
            }

            if ($sig !== '') {
                $q->orWhereRaw('LOWER(codigo_sig) = ?', [$sig]);
            }

            if ($sei !== '') {
                $q->orWhereRaw('LOWER(processo_sei) = ?', [$sei]);
            }
        });

        return $query
            ->limit(15)
            ->get(['id', 'titulo', 'codigo_sig', 'processo_sei', 'ciclo_id', 'status']);
    }

    public function justificativaValida(?string $justificativa): bool
    {
        return mb_strlen(trim((string) $justificativa)) >= 10;
    }

    private function normalizar(mixed $valor): string
    {
        return mb_strtolower(trim((string) $valor));
    }
}
