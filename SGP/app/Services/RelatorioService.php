<?php

namespace App\Services;

use App\Models\AcaoExtensiva;
use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\Evento;
use App\Models\HoraPedagogica;
use App\Models\Pca;
use App\Models\PlanoDeMeta;
use App\Models\VisitaTecnica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RelatorioService
{
    public function catalogo(): array
    {
        return array_values(config('relatorios.catalogo', []));
    }

    public function tipoExiste(string $tipo): bool
    {
        return array_key_exists($tipo, config('relatorios.catalogo', []));
    }

    public function obterDefinicao(string $tipo): array
    {
        if (! $this->tipoExiste($tipo)) {
            throw new InvalidArgumentException("Tipo de relatório inválido: {$tipo}");
        }

        return config("relatorios.catalogo.{$tipo}");
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{definicao: array, filtros: array, registros: Collection, total: int}
     */
    public function montar(string $tipo, array $filtros = []): array
    {
        $definicao = $this->obterDefinicao($tipo);
        $filtrosAplicados = $this->normalizarFiltros($filtros, $definicao['filtros'] ?? []);
        $registros = $this->consultar($tipo, $filtrosAplicados);

        return [
            'definicao' => $definicao,
            'filtros' => $filtrosAplicados,
            'registros' => $registros,
            'total' => $registros->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function consultar(string $tipo, array $filtros = []): Collection
    {
        $query = match ($tipo) {
            'cursos' => $this->queryCursos($filtros),
            'plano-de-metas' => $this->queryPlanoDeMetas($filtros),
            'pcas' => $this->queryPcas($filtros),
            'eixos' => $this->queryEixos($filtros),
            'visitas-tecnicas' => $this->queryVisitas($filtros),
            'horas-pedagogicas' => $this->queryHoras($filtros),
            'acoes-extensivas' => $this->queryAcoes($filtros),
            'eventos' => $this->queryEventos($filtros),
            default => throw new InvalidArgumentException("Tipo de relatório inválido: {$tipo}"),
        };

        return $query->get()->map(fn ($item) => $this->formatarLinha($item));
    }

    public function contagens(): array
    {
        return [
            'cursos' => Curso::query()->count(),
            'plano-de-metas' => PlanoDeMeta::query()->count(),
            'pcas' => Pca::query()->count(),
            'eixos' => CursoPorEixo::query()->count(),
            'visitas-tecnicas' => VisitaTecnica::query()->count(),
            'horas-pedagogicas' => HoraPedagogica::query()->count(),
            'acoes-extensivas' => AcaoExtensiva::query()->count(),
            'eventos' => Evento::query()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @param  array<int, string>  $permitidos
     * @return array<string, string>
     */
    private function normalizarFiltros(array $filtros, array $permitidos): array
    {
        $saida = [];

        foreach ($permitidos as $chave) {
            $valor = $filtros[$chave] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }
            $saida[$chave] = (string) $valor;
        }

        return $saida;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryCursos(array $filtros): Builder
    {
        $query = Curso::query()->orderBy('titulo');

        if (! empty($filtros['ano'])) {
            $query->where('ultima_revisao', 'like', "%{$filtros['ano']}%");
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }
        if (! empty($filtros['unidade'])) {
            $unidade = $filtros['unidade'];
            $query->where(function ($q) use ($unidade) {
                $q->where('unidade', $unidade)
                    ->orWhereJsonContains('unidades_oferta', $unidade);
            });
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryPlanoDeMetas(array $filtros): Builder
    {
        $query = PlanoDeMeta::query()->orderByDesc('ano')->orderBy('curso');

        if (! empty($filtros['ano'])) {
            $query->where('ano', $filtros['ano']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryPcas(array $filtros): Builder
    {
        $query = Pca::query()->orderByDesc('ano')->orderBy('titulo');

        if (! empty($filtros['ano'])) {
            $query->where('ano', $filtros['ano']);
        }
        if (! empty($filtros['unidade'])) {
            $query->where('unidade', $filtros['unidade']);
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryEixos(array $filtros): Builder
    {
        $query = CursoPorEixo::query()->orderBy('eixo')->orderBy('curso');

        if (! empty($filtros['ano'])) {
            $query->where('ano', $filtros['ano']);
        }
        if (! empty($filtros['unidade'])) {
            $query->where('unidade', $filtros['unidade']);
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryVisitas(array $filtros): Builder
    {
        $query = VisitaTecnica::query()->orderByDesc('data_solicitacao');

        if (! empty($filtros['unidade'])) {
            $query->where('unidade', $filtros['unidade']);
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryHoras(array $filtros): Builder
    {
        $query = HoraPedagogica::query()->orderByDesc('ano')->orderBy('pessoa');

        if (! empty($filtros['ano'])) {
            $query->where('ano', $filtros['ano']);
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryAcoes(array $filtros): Builder
    {
        $query = AcaoExtensiva::query()->orderByDesc('ultima_atualizacao')->orderBy('assunto');

        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryEventos(array $filtros): Builder
    {
        $query = Evento::query()->orderByDesc('data')->orderBy('nome');

        if (! empty($filtros['ano'])) {
            $query->where('ano', $filtros['ano']);
        }
        if (! empty($filtros['unidade'])) {
            $query->where('unidade', $filtros['unidade']);
        }
        if (! empty($filtros['eixo'])) {
            $query->where('eixo', $filtros['eixo']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    private function formatarLinha(mixed $item): array
    {
        $linha = $item->toArray();

        foreach (['data_solicitacao', 'data_visita_prevista', 'prazo_limite', 'data', 'ultima_atualizacao', 'data_inicio', 'data_fim'] as $campo) {
            if (isset($linha[$campo]) && $linha[$campo]) {
                $valor = $linha[$campo];
                if (is_string($valor) && strlen($valor) >= 10) {
                    $linha[$campo] = substr($valor, 0, 10);
                }
            }
        }

        if (array_key_exists('ativo', $linha) && is_bool($linha['ativo'])) {
            $linha['ativo'] = $linha['ativo'] ? 'Sim' : 'Não';
        }

        return $linha;
    }
}
