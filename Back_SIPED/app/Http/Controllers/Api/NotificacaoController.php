<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\CicloContextoService;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly NotificacaoService $notificacoes,
        private readonly CicloContextoService $cicloContexto,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar notificações.')) {
            return $negado;
        }

        $usuario = $request->user();
        $cicloId = $this->cicloContexto->id($request);
        $padrao = (int) config('notificacoes.limite_lista', 40);
        $maximo = (int) config('notificacoes.limite_lista_max', 100);
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = min($maximo, max(1, (int) $request->query('limit', $padrao)));

        return response()->json($this->notificacoes->listar(
            $usuario,
            $cicloId,
            $offset,
            $limit,
            $this->filtrosDaLista($request),
        ));
    }

    public function marcarLidas(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para atualizar notificações.')) {
            return $negado;
        }

        $validado = $request->validate([
            'chaves' => ['required', 'array', 'min:1', 'max:500'],
            'chaves.*' => ['required', 'string', 'max:120'],
        ]);

        $gravadas = $this->notificacoes->marcarLidas($request->user(), $validado['chaves']);

        return response()->json([
            'message' => $gravadas === 1
                ? 'Notificação marcada como lida.'
                : "{$gravadas} notificações marcadas como lidas.",
            'gravadas' => $gravadas,
        ]);
    }

    /**
     * @return array{q: string, modulo: string, nivel: string}
     */
    private function filtrosDaLista(Request $request): array
    {
        $busca = trim((string) $request->query('q', ''));
        if (mb_strlen($busca) > 80) {
            $busca = mb_substr($busca, 0, 80);
        }

        $modulo = (string) $request->query('modulo', '');
        if (! in_array($modulo, ['resolucoes', 'termos-referencia', 'visitas-tecnicas'], true)) {
            $modulo = '';
        }

        $nivel = (string) $request->query('nivel', '');
        if (! in_array($nivel, ['vencido', 'atencao'], true)) {
            $nivel = '';
        }

        return [
            'q' => $busca,
            'modulo' => $modulo,
            'nivel' => $nivel,
        ];
    }
}
