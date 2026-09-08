<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\JornadaPedagogicaRequest;
use App\Models\JornadaPedagogica;
use App\Services\JornadaPedagogicaAnexoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class JornadaPedagogicaController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly JornadaPedagogicaAnexoService $anexos,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar jornadas pedagógicas.')) {
            return $negado;
        }

        $query = JornadaPedagogica::query()->orderByDesc('data_inicio')->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('local', 'like', "%{$busca}%")
                    ->orWhere('espaco', 'like', "%{$busca}%")
                    ->orWhere('setores', 'like', "%{$busca}%")
                    ->orWhere('observacoes', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registros = $query->get()->map(fn (JornadaPedagogica $jornada) => $this->serializar($jornada));

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => JornadaPedagogica::query()->count(),
                'status' => config('jornadas_pedagogicas.status'),
                'sim_nao' => config('jornadas_pedagogicas.sim_nao'),
            ],
        ]);
    }

    public function store(JornadaPedagogicaRequest $request): JsonResponse
    {
        $payload = $this->aplicarAnexo($request, $request->safe()->except(['anexo']));
        $jornada = JornadaPedagogica::create($payload);

        return response()->json([
            'message' => 'Jornada Pedagógica cadastrada com sucesso.',
            'jornada' => $this->serializar($jornada->fresh()),
        ], 201);
    }

    public function show(Request $request, JornadaPedagogica $jornadaPedagogica): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta jornada pedagógica.')) {
            return $negado;
        }

        return response()->json([
            'jornada' => $this->serializar($jornadaPedagogica),
        ]);
    }

    public function update(JornadaPedagogicaRequest $request, JornadaPedagogica $jornadaPedagogica): JsonResponse
    {
        $payload = $this->aplicarAnexo($request, $request->safe()->except(['anexo']), $jornadaPedagogica);
        $jornadaPedagogica->update($payload);

        return response()->json([
            'message' => 'Jornada Pedagógica atualizada com sucesso.',
            'jornada' => $this->serializar($jornadaPedagogica->fresh()),
        ]);
    }

    public function destroy(Request $request, JornadaPedagogica $jornadaPedagogica): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir jornadas pedagógicas.',
            ], 403);
        }

        $titulo = $jornadaPedagogica->titulo;
        $this->anexos->apagar($jornadaPedagogica->anexo_path);
        $jornadaPedagogica->delete();

        return response()->json([
            'message' => "Jornada Pedagógica \"{$titulo}\" excluída com sucesso.",
        ]);
    }

    public function pdf(Request $request, JornadaPedagogica $jornadaPedagogica): JsonResponse|SymfonyResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para exportar esta jornada pedagógica.')) {
            return $negado;
        }

        $pdf = Pdf::loadView('jornadas-pedagogicas.plano', [
            'jornada' => $this->serializar($jornadaPedagogica),
            'emitidoEm' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'usuario' => $request->user()?->nome,
        ])->setPaper('a4', 'portrait');

        $slug = preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($jornadaPedagogica->titulo)) ?: 'jornada';

        return $pdf->download('jornada-pedagogica-'.$slug.'.pdf');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function aplicarAnexo(Request $request, array $payload, ?JornadaPedagogica $existente = null): array
    {
        if ($request->hasFile('anexo')) {
            if ($existente?->anexo_path) {
                $this->anexos->apagar($existente->anexo_path);
            }
            $payload['anexo_path'] = $this->anexos->salvar($request->file('anexo'));
        }

        unset($payload['anexo']);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(JornadaPedagogica $jornada): array
    {
        return array_merge($jornada->toArray(), [
            'data_inicio' => $jornada->data_inicio?->format('Y-m-d'),
            'data_fim' => $jornada->data_fim?->format('Y-m-d'),
            'data_pre_jornada' => $jornada->data_pre_jornada?->format('Y-m-d'),
            'anexo_url' => $this->anexos->urlPublica($jornada->anexo_path),
        ]);
    }
}
