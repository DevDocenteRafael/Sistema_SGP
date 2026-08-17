<?php

namespace App\Services;

use App\Models\AcaoExtensiva;
use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\Evento;
use App\Models\HoraPedagogica;
use App\Models\Pca;
use App\Models\PlanoDeMeta;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
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
            'resolucoes' => $this->queryResolucoes($filtros),
            'termos-referencia' => $this->queryTermosReferencia($filtros),
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
            'resolucoes' => Resolucao::query()->count(),
            'termos-referencia' => TermoReferencia::query()->count(),
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
        $chaves = array_values(array_unique([...$permitidos, 'busca']));

        foreach ($chaves as $chave) {
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
     * @param  list<string>  $campos
     */
    private function aplicarBusca(Builder $query, array $filtros, array $campos): void
    {
        if (empty($filtros['busca']) || $campos === []) {
            return;
        }

        $busca = $filtros['busca'];
        $query->where(function (Builder $q) use ($busca, $campos) {
            foreach ($campos as $indice => $campo) {
                if ($indice === 0) {
                    $q->where($campo, 'like', "%{$busca}%");
                    continue;
                }
                $q->orWhere($campo, 'like', "%{$busca}%");
            }
        });
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryResolucoes(array $filtros): Builder
    {
        $query = Resolucao::query()->orderByDesc('data_inicio_vigencia')->orderBy('numero');

        $this->aplicarBusca($query, $filtros, [
            'numero', 'curso_relacionado', 'categoria', 'resumo', 'relator', 'setor', 'observacoes',
        ]);

        if (! empty($filtros['ano'])) {
            $query->whereYear('data_inicio_vigencia', $filtros['ano'])
                ->orWhereYear('data_fim_vigencia', $filtros['ano']);
        }
        if (! empty($filtros['categoria'])) {
            $query->where('categoria', $filtros['categoria']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }
        if (! empty($filtros['setor'])) {
            $query->where('setor', $filtros['setor']);
        }
        if (! empty($filtros['relator'])) {
            $query->where('relator', 'like', "%{$filtros['relator']}%")
                ->orWhere('setor', 'like', "%{$filtros['relator']}%");
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function queryTermosReferencia(array $filtros): Builder
    {
        $query = TermoReferencia::query()->orderByDesc('prazo_deadline')->orderBy('nome');

        $this->aplicarBusca($query, $filtros, [
            'nome', 'eixo', 'processo_sei', 'status', 'observacao',
        ]);

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
    private function queryCursos(array $filtros): Builder
    {
        $query = Curso::query()->orderBy('titulo');

        $this->aplicarBusca($query, $filtros, [
            'titulo', 'codigo_sig', 'processo_sei', 'eixo', 'unidade',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'segmento', 'curso', 'tipo', 'numero_sei', 'codigo_sig',
            'mes_entrega', 'status', 'status_final', 'observacao',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'titulo', 'numero_sei', 'codigo_sig', 'eixo', 'unidade', 'semestre', 'status', 'observacao',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'curso', 'eixo', 'unidade', 'codigo', 'instrutores', 'observacao',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'unidade', 'eixo', 'processo_sei', 'responsavel', 'status', 'relatorio', 'observacao',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'matricula', 'pessoa', 'segmento', 'eixo', 'processo_sei', 'motivo', 'status', 'observacao',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'atribuido', 'eixo', 'numero_processo_sei', 'assunto', 'objetivo', 'tipo', 'status',
        ]);

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

        $this->aplicarBusca($query, $filtros, [
            'nome', 'unidade', 'eixo', 'equipe', 'acao_vinculada', 'status', 'observacao',
        ]);

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

        foreach (['data_solicitacao', 'data_visita_prevista', 'prazo_limite', 'data', 'ultima_atualizacao', 'data_inicio', 'data_fim', 'data_inicio_vigencia', 'data_fim_vigencia', 'prazo_deadline'] as $campo) {
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
