<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\PaginatesIndex;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use App\Services\CadastroAuditoriaService;
use App\Services\UsuarioFotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    use PaginatesIndex;
    public function __construct(
        private CadastroAuditoriaService $auditoria,
        private UsuarioFotoService $fotos,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Usuario::query()->orderBy('nome');

        if ($request->filled('perfil')) {
            $query->where('perfil', $request->perfil);
        }

        if ($request->filled('status')) {
            $query->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('telefone', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%");
            });
        }

        $paginator = $this->paginar($query, $request);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => $this->metaPaginacao($paginator),
        ]);
    }

    /**
     * Só Root altera, inativa ou cria outro Root.
     */
    private function negarSeRootProtegido(Request $request, Usuario $alvo): ?JsonResponse
    {
        if ($alvo->isRoot() && ! $request->user()?->isRoot()) {
            return response()->json([
                'message' => 'Somente o perfil Root pode alterar um usuário Root.',
            ], 403);
        }

        return null;
    }

    public function store(UsuarioRequest $request): JsonResponse
    {
        $dados = $request->safe()->except(['foto', 'remover_foto']);
        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = $dados['status'] ?? true;

        if ($request->hasFile('foto')) {
            $dados['foto'] = $this->fotos->salvar($request->file('foto'));
        }

        $usuario = Usuario::create($dados);

        $this->auditoria->registrarModelo(CadastroAuditoriaService::ACAO_CRIAR, $usuario);

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso. Informe o e-mail e a senha ao colaborador.',
            'usuario' => $usuario->fresh(),
        ], 201);
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json([
            'usuario' => $usuario,
        ]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario): JsonResponse
    {
        if ($negado = $this->negarSeRootProtegido($request, $usuario)) {
            return $negado;
        }

        $dados = $request->safe()->except(['foto', 'remover_foto']);

        if (! empty($dados['senha'])) {
            $dados['senha'] = Hash::make($dados['senha']);
        } else {
            unset($dados['senha']);
        }

        if (
            $usuario->temPerfilAdministrativo()
            && isset($dados['perfil'])
            && ! in_array($dados['perfil'], [Usuario::PERFIL_ROOT, Usuario::PERFIL_ADMINISTRADOR], true)
            && $this->ehUltimoAdministradorAtivo($usuario)
        ) {
            return response()->json([
                'message' => 'Não é possível alterar o perfil do último administrador ativo.',
            ], 422);
        }

        if (
            $usuario->temPerfilAdministrativo()
            && array_key_exists('status', $dados)
            && $dados['status'] === false
            && $this->ehUltimoAdministradorAtivo($usuario)
        ) {
            return response()->json([
                'message' => 'Não é possível inativar o último administrador ativo.',
            ], 422);
        }

        if (array_key_exists('status', $dados) && $dados['status'] === false && $request->user()?->id === $usuario->id) {
            return response()->json([
                'message' => 'Você não pode inativar o próprio usuário.',
            ], 422);
        }

        $statusAnterior = (bool) $usuario->status;

        $fotoAnterior = $usuario->caminhoFoto();

        if ($request->boolean('remover_foto')) {
            $dados['foto'] = null;
            $this->fotos->apagar($fotoAnterior);
        } elseif ($request->hasFile('foto')) {
            $dados['foto'] = $this->fotos->salvar($request->file('foto'));
            $this->fotos->apagar($fotoAnterior);
        }

        $usuario->fill($dados);
        $alterados = array_keys($usuario->getDirty());
        $usuario->save();

        // Inativação ou troca de senha invalida sessões abertas.
        if (in_array('status', $alterados, true) && $usuario->status === false) {
            $usuario->tokens()->delete();
        } elseif (in_array('senha', $alterados, true)) {
            $usuario->tokens()->delete();
        }

        $this->auditoria->registrarModelo(
            CadastroAuditoriaService::ACAO_EDITAR,
            $usuario,
            null,
            ['alterados' => $alterados],
        );

        if (in_array('status', $alterados, true)) {
            $this->auditoria->registrarModelo(
                $usuario->status ? CadastroAuditoriaService::ACAO_REATIVAR : CadastroAuditoriaService::ACAO_INATIVAR,
                $usuario,
                null,
                ['status_anterior' => $statusAnterior],
            );
        }

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    /**
     * Usuários não são excluídos: DELETE inativa, revoga os tokens e mantém o histórico.
     */
    public function destroy(Request $request, Usuario $usuario): JsonResponse
    {
        if ($request->user()->id === $usuario->id) {
            return response()->json([
                'message' => 'Você não pode inativar o próprio usuário.',
            ], 422);
        }

        if ($negado = $this->negarSeRootProtegido($request, $usuario)) {
            return $negado;
        }

        if ($usuario->temPerfilAdministrativo() && $this->ehUltimoAdministradorAtivo($usuario)) {
            return response()->json([
                'message' => 'Não é possível inativar o último administrador ativo.',
            ], 422);
        }

        if (! $usuario->status) {
            return response()->json([
                'message' => 'Usuário já está inativo.',
                'usuario' => $usuario,
            ]);
        }

        $usuario->forceFill(['status' => false])->save();
        $usuario->tokens()->delete();

        $this->auditoria->registrarModelo(CadastroAuditoriaService::ACAO_INATIVAR, $usuario);

        return response()->json([
            'message' => 'Usuário inativado. O acesso foi bloqueado e o histórico foi mantido.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    public function reativar(Request $request, Usuario $usuario): JsonResponse
    {
        if ($negado = $this->negarSeRootProtegido($request, $usuario)) {
            return $negado;
        }

        if ($usuario->status) {
            return response()->json([
                'message' => 'Usuário já está ativo.',
                'usuario' => $usuario,
            ]);
        }

        $usuario->forceFill(['status' => true])->save();
        $this->auditoria->registrarModelo(CadastroAuditoriaService::ACAO_REATIVAR, $usuario);

        return response()->json([
            'message' => 'Usuário reativado.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    /** Root e Administrador contam como perfis administrativos. */
    private function ehUltimoAdministradorAtivo(Usuario $usuario): bool
    {
        return Usuario::query()
            ->whereIn('perfil', [Usuario::PERFIL_ROOT, Usuario::PERFIL_ADMINISTRADOR])
            ->where('status', true)
            ->where('id', '!=', $usuario->id)
            ->doesntExist();
    }
}
