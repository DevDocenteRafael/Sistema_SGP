<?php

namespace App\Services;

use App\Models\AcaoExtensiva;
use App\Models\Curso;
use App\Models\Evento;
use App\Models\HoraPedagogica;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use App\Models\VisitaTecnica;

class DashboardService
{
    /**
     * Payload enxuto para o dashboard (evita 7 GETs com tabelas completas).
     *
     * @return array<string, mixed>
     */
    public function resumo(): array
    {
        $cursos = Curso::query()
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

        $eixos = Curso::query()
            ->whereNotNull('eixo')
            ->where('eixo', '!=', '')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->all();

        $status = Curso::query()
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

        return [
            'cursos' => $cursos,
            'visitas' => $visitas,
            'horas' => $horas,
            'contagens' => [
                'visitas' => count($visitas),
                'horas' => count($horas),
                'acoes' => AcaoExtensiva::query()->count(),
                'eventos' => Evento::query()->count(),
                'resolucoes' => $resolucoesLeves->count(),
                'termos' => $termosLeves->count(),
            ],
            'resolucoes_contagens' => ResolucaoVigenciaService::contarPorSemaforo($resolucoesLeves),
            'termos_contagens' => TermoReferenciaPrazoService::contarPorPrazo($termosLeves),
            'meta' => [
                'eixos' => $eixos,
                'status' => $status,
            ],
        ];
    }
}
