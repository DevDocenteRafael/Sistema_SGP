<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait PaginatesIndex
{
    protected function paginar($query, Request $request, int $padrao = 10): LengthAwarePaginator
    {
        $porPagina = min(max((int) $request->input('per_page', $padrao), 1), 100);

        return $query->paginate($porPagina);
    }

    protected function metaPaginacao(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
    }
}