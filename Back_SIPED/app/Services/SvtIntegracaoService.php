<?php

namespace App\Services;

use App\Models\ImportacaoHistorico;
use App\Models\VisitaTecnica;
use App\Support\CatalogoOficial;
use App\Support\OrigemDados;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Recebe visitas do SVT (Sistema de Visitas Técnicas) e as grava no SIPED.
 * O SVT é a fonte da verdade: cada visita é identificada pelo id do SVT
 * (external_id) e atualizada a cada envio. Nada é apagado fisicamente.
 */
class SvtIntegracaoService
{
    public static function sistema(): string
    {
        return (string) config('svt.sistema', 'svt');
    }

    public static function modoSvt(): bool
    {
        return config('svt.modo') === 'svt';
    }

    public static function integracaoAtiva(): bool
    {
        return is_string(config('svt.token')) && trim((string) config('svt.token')) !== '';
    }

    public static function urlVisita(?string $externalId): ?string
    {
        $modelo = config('svt.visita_url');
        if (! $externalId || ! is_string($modelo) || ! str_contains($modelo, '{id}') || ! preg_match('#^https?://#i', $modelo)) {
            return null;
        }

        return str_replace('{id}', rawurlencode($externalId), $modelo);
    }

    public static function baseUrl(): ?string
    {
        $url = config('svt.base_url');

        return is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    public static function ehDoSvt(VisitaTecnica $visita): bool
    {
        return $visita->source_system === self::sistema();
    }

    /**
     * @param  list<array<string, mixed>>  $visitas
     * @return array{novos: int, atualizados: int, excluidos: int, ignorados: int, erros: list<array{id: mixed, mensagem: string}>, historico_id: int}
     */
    public function sincronizar(array $visitas): array
    {
        $resumo = ['novos' => 0, 'atualizados' => 0, 'excluidos' => 0, 'ignorados' => 0, 'erros' => []];

        foreach ($visitas as $indice => $dados) {
            $id = is_scalar($dados['id'] ?? null) ? trim((string) $dados['id']) : '';
            if ($id === '') {
                $resumo['ignorados']++;
                $resumo['erros'][] = ['id' => null, 'mensagem' => "Item {$indice}: sem id do SVT; ignorado."];

                continue;
            }

            try {
                DB::transaction(function () use ($id, $dados, &$resumo) {
                    $visita = VisitaTecnica::withTrashed()
                        ->where('source_system', self::sistema())
                        ->where('external_id', $id)
                        ->first();

                    // O SVT pode marcar a visita como excluída: exclusão lógica (restaurável).
                    if (filter_var($dados['excluida'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        if ($visita && ! $visita->trashed()) {
                            $visita->delete();
                            $resumo['excluidos']++;
                        } else {
                            $resumo['ignorados']++;
                        }

                        return;
                    }

                    $campos = $this->mapear($dados);
                    if ($visita) {
                        if ($visita->trashed()) {
                            $visita->restore();
                        }
                        $visita->fill($campos);
                        $visita->synced_at = now();
                        $visita->save();
                        $resumo['atualizados']++;

                        return;
                    }

                    $nova = new VisitaTecnica($campos);
                    $nova->source_type = OrigemDados::TIPO_INTEGRACAO;
                    $nova->source_system = self::sistema();
                    $nova->external_id = $id;
                    $nova->synced_at = now();
                    $nova->save();
                    $resumo['novos']++;
                });
            } catch (Throwable $e) {
                $resumo['ignorados']++;
                $resumo['erros'][] = ['id' => $id, 'mensagem' => 'Não gravada: '.mb_substr($e->getMessage(), 0, 200)];
            }
        }

        $historico = ImportacaoHistorico::query()->create([
            'tipo' => 'integracao',
            'modulo' => 'visitas-tecnicas',
            'modulo_label' => 'Visitas Técnicas (SVT)',
            'situacao' => ImportacaoHistorico::SITUACAO_CONCLUIDA,
            'arquivo_nome' => 'SVT — envio por API',
            'novos' => $resumo['novos'],
            'atualizados' => $resumo['atualizados'],
            'ignorados' => $resumo['ignorados'],
            'erros' => count($resumo['erros']),
            'mensagem' => sprintf(
                'Sincronização SVT: %d nova(s), %d atualizada(s), %d excluída(s), %d ignorada(s).',
                $resumo['novos'], $resumo['atualizados'], $resumo['excluidos'], $resumo['ignorados'],
            ),
            'detalhes' => ['erros' => array_slice($resumo['erros'], 0, 500), 'excluidos' => $resumo['excluidos']],
        ]);

        return $resumo + ['historico_id' => $historico->id];
    }

    /**
     * Converte o formato do SVT para as colunas do SIPED (o que não vier, fica como está).
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function mapear(array $dados): array
    {
        $texto = fn (string $chave, int $max) => array_key_exists($chave, $dados)
            ? (is_scalar($dados[$chave]) && trim((string) $dados[$chave]) !== '' ? mb_substr(trim((string) $dados[$chave]), 0, $max) : null)
            : false;
        $data = function (string $chave) use ($dados) {
            if (! array_key_exists($chave, $dados)) {
                return false;
            }
            try {
                return $dados[$chave] ? Carbon::parse((string) $dados[$chave])->toDateString() : null;
            } catch (Throwable) {
                return null;
            }
        };

        $campos = [
            'unidade' => $texto('unidade', 100),
            'eixo' => $texto('eixo', 150),
            'processo_sei' => $texto('processo_sei', 100),
            'data_solicitacao' => $data('data_solicitacao'),
            'data_visita_prevista' => $data('data_visita_prevista'),
            'prazo_limite' => $data('prazo_limite'),
            'status' => $texto('status', 50),
            'etapa_svt' => $texto('etapa', 60),
            'responsavel' => $texto('responsavel', 150),
            'relatorio' => $texto('relatorio', 2000),
            'observacao' => $texto('observacao', 2000),
        ];

        if (is_string($campos['eixo'])) {
            $campos['eixo'] = CatalogoOficial::canonicalizarEixo($campos['eixo']) ?? $campos['eixo'];
        }
        if (is_string($campos['status'])) {
            $oficial = collect(config('visitas_tecnicas.status', []))
                ->first(fn ($s) => CatalogoOficial::chave($s) === CatalogoOficial::chave($campos['status']));
            $campos['status'] = $oficial ?? $campos['status'];
        }

        return array_filter($campos, fn ($valor) => $valor !== false);
    }

    /**
     * @return array<string, mixed>
     */
    public function situacao(): array
    {
        $ultima = ImportacaoHistorico::query()
            ->where('tipo', 'integracao')
            ->where('modulo', 'visitas-tecnicas')
            ->orderByDesc('id')
            ->first();

        return [
            'modo' => self::modoSvt() ? 'svt' : 'local',
            'integracao_ativa' => self::integracaoAtiva(),
            'svt_url' => self::baseUrl(),
            'ultima_sincronizacao' => $ultima?->created_at?->toISOString(),
            'ultima_mensagem' => $ultima?->mensagem,
            'visitas_do_svt' => VisitaTecnica::query()->where('source_system', self::sistema())->count(),
        ];
    }
}
