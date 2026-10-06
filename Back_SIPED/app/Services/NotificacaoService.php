<?php

namespace App\Services;

use App\Models\NotificacaoLeitura;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use App\Support\CatalogoOficial;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificacaoService
{
    /**
     * Alertas internos de prazo (Atenção e Vencido) para o sininho,
     * respeitando o escopo de eixos do usuário.
     *
     *
     * @param  array{q?: string, modulo?: string, nivel?: string}  $filtros
     * @return array{itens: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listar(Usuario $usuario, ?int $cicloId = null, int $offset = 0, ?int $limit = null, array $filtros = []): array
    {
        $hoje = Carbon::now()->startOfDay();
        $lidas = NotificacaoLeitura::query()
            ->where('usuario_id', $usuario->id)
            ->pluck('lida_em', 'chave');

        $itens = collect()
            ->concat($this->deResolucoes($hoje))
            ->concat($this->deTermos($hoje))
            ->concat($this->deVisitas($hoje, $cicloId))
            ->filter(fn (array $item) => $this->dentroDoEscopo($usuario, $item))
            ->map(function (array $item) use ($lidas) {
                $item['lida'] = $lidas->has($item['chave']);

                return $item;
            })
            ->sort(function (array $a, array $b) {
                $peso = ['vencido' => 0, 'atencao' => 1];
                $cmp = ($peso[$a['nivel']] ?? 9) <=> ($peso[$b['nivel']] ?? 9);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcmp((string) $a['data_prazo'], (string) $b['data_prazo']);
            })
            ->values();

        $naoLidas = $itens->where('lida', false);
        $filtrados = $this->aplicarFiltros($itens, $filtros);

        $padrao = (int) config('notificacoes.limite_lista', 40);
        $maximo = (int) config('notificacoes.limite_lista_max', 100);
        $limite = min($maximo, max(1, $limit ?? $padrao));
        $deslocamento = max(0, $offset);
        $total = $filtrados->count();
        $fatia = $filtrados->slice($deslocamento, $limite)->values();

        return [
            'itens' => $fatia->all(),
            'meta' => [
                'total' => $total,
                'total_geral' => $itens->count(),
                'nao_lidas' => $naoLidas->count(),
                'offset' => $deslocamento,
                'limit' => $limite,
                'has_more' => ($deslocamento + $fatia->count()) < $total,
                'filtrado' => $this->temFiltro($filtros),
                'por_nivel' => [
                    'vencido' => $naoLidas->where('nivel', 'vencido')->count(),
                    'atencao' => $naoLidas->where('nivel', 'atencao')->count(),
                ],
                'escopo' => [
                    'todos' => $usuario->veTodasNotificacoes(),
                    'eixos' => $usuario->eixosResponsavel(),
                ],
                'por_modulo' => [
                    'resolucoes' => $naoLidas->where('modulo', 'resolucoes')->count(),
                    'termos-referencia' => $naoLidas->where('modulo', 'termos-referencia')->count(),
                    'visitas-tecnicas' => $naoLidas->where('modulo', 'visitas-tecnicas')->count(),
                ],
            ],
        ];
    }

    /**
     * Item sem eixo (ex.: Resoluções) é geral e aparece para todos.
     *
     * @param  array<string, mixed>  $item
     */
    private function dentroDoEscopo(Usuario $usuario, array $item): bool
    {
        if ($usuario->veTodasNotificacoes()) {
            return true;
        }

        $eixo = $item['eixo'] ?? null;
        if (! $eixo) {
            return true;
        }

        return in_array($eixo, $usuario->eixosResponsavel(), true);
    }

    /**
     * @param  array{q?: string, modulo?: string, nivel?: string}  $filtros
     */
    private function temFiltro(array $filtros): bool
    {
        return trim((string) ($filtros['q'] ?? '')) !== ''
            || trim((string) ($filtros['modulo'] ?? '')) !== ''
            || trim((string) ($filtros['nivel'] ?? '')) !== '';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $itens
     * @param  array{q?: string, modulo?: string, nivel?: string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    private function aplicarFiltros(Collection $itens, array $filtros): Collection
    {
        $modulo = trim((string) ($filtros['modulo'] ?? ''));
        $nivel = trim((string) ($filtros['nivel'] ?? ''));
        $busca = mb_strtolower(trim((string) ($filtros['q'] ?? '')));

        return $itens
            ->when($modulo !== '', fn (Collection $lista) => $lista->where('modulo', $modulo))
            ->when($nivel !== '', fn (Collection $lista) => $lista->where('nivel', $nivel))
            ->when($busca !== '', function (Collection $lista) use ($busca) {
                return $lista->filter(function (array $item) use ($busca) {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($item['titulo'] ?? ''),
                        (string) ($item['detalhe'] ?? ''),
                        (string) ($item['mensagem'] ?? ''),
                        (string) ($item['rotulo_modulo'] ?? ''),
                        (string) ($item['registro_id'] ?? ''),
                        (string) ($item['data_prazo'] ?? ''),
                    ]));

                    return str_contains($haystack, $busca);
                });
            })
            ->values();
    }

    /**
     * @param  list<string>  $chaves
     */
    public function marcarLidas(Usuario $usuario, array $chaves): int
    {
        $agora = now();
        $gravadas = 0;

        foreach (array_unique(array_filter($chaves)) as $chave) {
            NotificacaoLeitura::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'chave' => (string) $chave,
                ],
                ['lida_em' => $agora],
            );
            $gravadas++;
        }

        return $gravadas;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function deResolucoes(Carbon $hoje): Collection
    {
        return Resolucao::query()
            ->where(function ($query) use ($hoje) {
                $limiteAtencao = $hoje->copy()->addMonthsNoOverflow(
                    (int) config('resolucoes.alerta_preventivo_meses', 6)
                );
                $query->whereDate('data_fim_vigencia', '<=', $limiteAtencao)
                    ->orWhere(function ($semFim) use ($limiteAtencao) {
                        $semFim->whereNull('data_fim_vigencia')
                            ->whereDate(
                                'data_inicio_vigencia',
                                '<=',
                                $limiteAtencao->copy()->subYears(ResolucaoVigenciaService::vigenciaAnos())
                            );
                    });
            })
            ->get(['id', 'numero', 'curso_relacionado', 'status', 'data_inicio_vigencia', 'data_fim_vigencia'])
            ->map(function (Resolucao $resolucao) use ($hoje) {
                $status = ResolucaoVigenciaService::statusVigencia($resolucao);
                $nivel = match ($status) {
                    'vencida' => 'vencido',
                    'atencao' => 'atencao',
                    default => null,
                };

                if (! $nivel) {
                    return null;
                }

                $prazo = $resolucao->data_fim_vigencia?->toDateString();

                return $this->montar(
                    modulo: 'resolucoes',
                    registroId: $resolucao->id,
                    nivel: $nivel,
                    titulo: $resolucao->numero ?: 'Resolução #'.$resolucao->id,
                    detalhe: $resolucao->curso_relacionado,
                    prazo: $prazo,
                    hoje: $hoje,
                    rota: '/app/controle-de-resolucoes',
                    rotuloModulo: 'Resolução',
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function deTermos(Carbon $hoje): Collection
    {
        return TermoReferencia::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['Concluído', 'Arquivado']);
            })
            ->where(function ($query) use ($hoje) {
                $limiteAtencao = $hoje->copy()->addDays(TermoReferenciaPrazoService::diasAtencao());
                // Vencimento da Ata quando existir; sem Ata, prazo do TR.
                $query->whereDate('data_vencimento_ata', '<=', $limiteAtencao)
                    ->orWhere(function ($semAta) use ($limiteAtencao) {
                        $semAta->whereNull('data_vencimento_ata')
                            ->where(function ($prazo) use ($limiteAtencao) {
                                $prazo->whereDate('prazo_deadline', '<=', $limiteAtencao)
                                    ->orWhereNull('prazo_deadline');
                            });
                    });
            })
            ->get(['id', 'nome', 'eixo', 'processo_sei', 'status', 'prazo_deadline', 'data_vencimento_ata'])
            ->map(function (TermoReferencia $termo) use ($hoje) {
                $status = TermoReferenciaPrazoService::statusDoTermo($termo);
                $nivel = match ($status) {
                    'vencido' => 'vencido',
                    'atencao' => 'atencao',
                    default => null,
                };

                if (! $nivel) {
                    return null;
                }

                return $this->montar(
                    modulo: 'termos-referencia',
                    registroId: $termo->id,
                    nivel: $nivel,
                    titulo: $termo->nome ?: 'Termo de referência #'.$termo->id,
                    detalhe: $termo->processo_sei,
                    prazo: TermoReferenciaPrazoService::dataReferencia($termo),
                    hoje: $hoje,
                    rota: '/app/termos-de-referencia',
                    rotuloModulo: 'Termo de referência',
                    eixo: $termo->eixo,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function deVisitas(Carbon $hoje, ?int $cicloId): Collection
    {
        $alertaDias = (int) config('notificacoes.visitas_alerta_dias', 7);
        $limiteAlerta = $hoje->copy()->addDays($alertaDias);

        return VisitaTecnica::query()
            ->when($cicloId, fn ($query) => $query->where('ciclo_id', $cicloId))
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['Realizada', 'Cancelada', 'Recusada']);
            })
            ->where(function ($query) use ($limiteAlerta) {
                $query->whereRaw('LOWER(status) = ?', ['atrasada'])
                    ->orWhereDate('prazo_limite', '<=', $limiteAlerta);
            })
            ->get(['id', 'unidade', 'eixo', 'processo_sei', 'status', 'prazo_limite'])
            ->map(function (VisitaTecnica $visita) use ($hoje, $limiteAlerta) {
                $prazo = $visita->prazo_limite?->startOfDay();
                $atrasada = strcasecmp((string) $visita->status, 'Atrasada') === 0;

                if (! $prazo && ! $atrasada) {
                    return null;
                }

                $nivel = null;
                if ($atrasada || ($prazo && $hoje->gt($prazo))) {
                    $nivel = 'vencido';
                } elseif ($prazo && $prazo->lte($limiteAlerta)) {
                    $nivel = 'atencao';
                }

                if (! $nivel) {
                    return null;
                }

                $titulo = $visita->unidade
                    ? 'Visita · '.$visita->unidade
                    : 'Visita técnica #'.$visita->id;

                return $this->montar(
                    modulo: 'visitas-tecnicas',
                    registroId: $visita->id,
                    nivel: $nivel,
                    titulo: $titulo,
                    detalhe: $visita->processo_sei ?: $visita->eixo,
                    prazo: $visita->prazo_limite?->toDateString(),
                    hoje: $hoje,
                    rota: '/app/visitas-tecnicas',
                    rotuloModulo: 'Visita técnica',
                    eixo: $visita->eixo,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function montar(
        string $modulo,
        int $registroId,
        string $nivel,
        string $titulo,
        ?string $detalhe,
        ?string $prazo,
        Carbon $hoje,
        string $rota,
        string $rotuloModulo,
        ?string $eixo = null,
    ): array {
        $chave = $modulo.':'.$registroId.':'.$nivel;

        return [
            'chave' => $chave,
            'modulo' => $modulo,
            'rotulo_modulo' => $rotuloModulo,
            'registro_id' => $registroId,
            'nivel' => $nivel,
            'titulo' => $titulo,
            'detalhe' => $detalhe ?: null,
            'mensagem' => $this->mensagem($nivel, $prazo, $hoje),
            'data_prazo' => $prazo,
            'rota' => $rota,
            'eixo' => $eixo ? (CatalogoOficial::canonicalizarEixo($eixo) ?? $eixo) : null,
        ];
    }

    private function mensagem(string $nivel, ?string $prazo, Carbon $hoje): string
    {
        $formatada = $prazo ? Carbon::parse($prazo)->format('d/m/Y') : null;

        if ($nivel === 'vencido') {
            return $formatada
                ? 'Fora do prazo desde '.$formatada.'.'
                : 'Prazo encerrado.';
        }

        if (! $prazo) {
            return 'Prazo em atenção.';
        }

        $dias = (int) $hoje->diffInDays(Carbon::parse($prazo)->startOfDay(), false);

        if ($dias === 0) {
            return 'Vence hoje ('.$formatada.').';
        }

        $unidade = $dias === 1 ? 'dia' : 'dias';

        return "Atenção — vence em {$dias} {$unidade} ({$formatada}).";
    }
}
