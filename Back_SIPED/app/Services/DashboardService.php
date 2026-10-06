<?php

namespace App\Services;

use App\Models\AcaoExtensiva;
use App\Models\Curso;
use App\Models\Evento;
use App\Models\HoraPedagogica;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use App\Models\UnidadeOferta;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * Payload enxuto para o dashboard (evita 7 GETs com tabelas completas).
     *
     * @return array<string, mixed>
     */
    public function resumo(array $filtros = []): array
    {
        $cursos = \App\Support\ConsultaCatalogoCursos::query($filtros)
            ->select([
                'id',
                'titulo',
                'eixo',
                'status',
                'tipo',
                'unidade',
                'unidades_oferta',
                'ultima_revisao',
                'carga_horaria',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (Curso $curso) => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'eixo' => $curso->eixo,
                'status' => $curso->status,
                'tipo' => $curso->tipo,
                'unidade' => $curso->unidade,
                'unidades_oferta' => $curso->unidades_oferta ?? [],
                'ultima_revisao' => $curso->ultima_revisao,
                'carga_horaria' => $curso->carga_horaria,
                'ano' => $curso->ultima_revisao ? substr((string) $curso->ultima_revisao, 0, 4) : null,
            ])
            ->values()
            ->all();

        $eixos = \App\Support\CatalogoOficial::eixos();

        $cicloId = \App\Support\ConsultaCatalogoCursos::ciclo($filtros['ciclo_id'] ?? null);

        $status = Curso::query()
            ->when($cicloId, fn ($q) => $q->where('ciclo_id', $cicloId))
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->all();

        $resolucoesLeves = Resolucao::query()
            ->get(['id', 'status', 'data_inicio_vigencia', 'data_fim_vigencia']);

        $termosLeves = TermoReferencia::query()
            ->get(['id', 'prazo_deadline']);

        $visitas = VisitaTecnica::query()
            ->when($cicloId, fn ($q) => $q->where('ciclo_id', $cicloId))
            ->select([
                'id',
                'status',
                'eixo',
                'unidade',
                'responsavel',
                'prazo_limite',
                'data_solicitacao',
                'data_visita_prevista',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (VisitaTecnica $item) => [
                'id' => $item->id,
                'status' => $item->status,
                'eixo' => $item->eixo,
                'unidade' => $item->unidade,
                'responsavel' => $item->responsavel,
                'prazo_limite' => optional($item->prazo_limite)?->format('Y-m-d'),
                'data_solicitacao' => optional($item->data_solicitacao)?->format('Y-m-d'),
                'data_visita_prevista' => optional($item->data_visita_prevista)?->format('Y-m-d'),
            ])
            ->values()
            ->all();

        $horas = HoraPedagogica::query()
            ->when($cicloId, fn ($q) => $q->where('ciclo_id', $cicloId))
            ->select([
                'id',
                'status',
                'eixo',
                'segmento',
                'pessoa',
                'ano',
                'ativo',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (HoraPedagogica $item) => [
                'id' => $item->id,
                'status' => $item->status,
                'eixo' => $item->eixo,
                'segmento' => $item->segmento,
                'pessoa' => $item->pessoa,
                'ano' => $item->ano,
                'ativo' => $item->ativo,
            ])
            ->values()
            ->all();

        $acoesLeves = AcaoExtensiva::query()
            ->when($cicloId, fn ($q) => $q->where('ciclo_id', $cicloId))
            ->get(['priorizacao']);

        $eventosLeves = Evento::query()
            ->when($cicloId, fn ($q) => $q->where('ciclo_id', $cicloId))
            ->get(['status']);

        $estruturas = UnidadeOferta::query()
            ->get(['nome', 'tipo', 'ativo']);
        $estruturasPorTipo = $estruturas
            ->where('ativo', true)
            ->groupBy('tipo')
            ->map->count()
            ->all();
        $usuarios = Usuario::query()
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as ativos')
            ->first();
        $totalUsuarios = (int) ($usuarios->total ?? 0);
        $usuariosAtivos = (int) ($usuarios->ativos ?? 0);

        return [
            'cursos' => $cursos,
            'visitas' => $visitas,
            'horas' => $horas,
            'estruturas' => $estruturas,
            'contagens' => [
                'visitas' => count($visitas),
                'horas' => count($horas),
                'acoes' => $acoesLeves->count(),
                'eventos' => $eventosLeves->count(),
                'resolucoes' => $resolucoesLeves->count(),
                'termos' => $termosLeves->count(),
                'estruturas' => $estruturas->count(),
                'estruturas_faculdade' => (int) ($estruturasPorTipo['faculdade'] ?? 0),
                'estruturas_polo' => (int) ($estruturasPorTipo['polo'] ?? 0),
                'estruturas_unidade' => (int) (($estruturasPorTipo['unidade'] ?? 0) + ($estruturasPorTipo['cep'] ?? 0)),
                'usuarios' => $totalUsuarios,
                'usuarios_ativos' => $usuariosAtivos,
                'usuarios_inativos' => $totalUsuarios - $usuariosAtivos,
            ],
            'distribuicoes' => [
                'horas' => $this->contarDistribuicao($horas, 'status', [
                    ...config('horas_pedagogicas.status', []),
                    ...config('horas_pedagogicas.status_legados', []),
                ]),
                'acoes' => $this->contarDistribuicao($acoesLeves->all(), 'priorizacao', config('acoes_extensivas.priorizacoes', [])),
                'eventos' => $this->contarDistribuicao($eventosLeves->all(), 'status', config('eventos.status', [])),
                'visitas' => $this->contarDistribuicao($visitas, 'status', config('visitas_tecnicas.status', [])),
            ],
            'resolucoes_contagens' => ResolucaoVigenciaService::contarPorSemaforo($resolucoesLeves),
            'termos_contagens' => TermoReferenciaPrazoService::contarPorPrazo($termosLeves),
            'meta' => [
                'eixos' => $eixos,
                'ciclo_id' => $cicloId,
                'ciclo' => $cicloId ? \App\Models\Ciclo::query()->find($cicloId)?->paraMeta() : null,
                'status' => $status,
                'unidades' => UnidadeOferta::nomesAtivos(),
            ],
        ];
    }

    /**
     * @param  iterable<array<string, mixed>|object>  $registros
     * @param  list<string>  $categorias
     * @return array<string, int>
     */
    private function contarDistribuicao(iterable $registros, string $campo, array $categorias): array
    {
        $contagens = [];
        foreach ($categorias as $categoria) {
            $contagens[Str::slug($categoria, '_')] = 0;
        }
        $contagens['sem_classificacao'] = 0;
        $contagens['outros'] = 0;

        foreach ($registros as $registro) {
            $valor = data_get($registro, $campo);
            $chave = Str::slug(trim((string) $valor), '_');

            if ($chave === '') {
                $contagens['sem_classificacao']++;
            } elseif (array_key_exists($chave, $contagens) && ! in_array($chave, ['sem_classificacao', 'outros'], true)) {
                $contagens[$chave]++;
            } else {
                $contagens['outros']++;
            }
        }

        return $contagens;
    }
}
