<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use App\Services\CadastroAuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function __construct(
        private CadastroAuditoriaService $auditoria,
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

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(UsuarioRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = $dados['status'] ?? true;

        $usuario = Usuario::create($dados);

        $this->auditoria->registrarModelo(CadastroAuditoriaService::ACAO_CRIAR, $usuario);

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso. Informe o e-mail e a senha ao colaborador.',
            'usuario' => $usuario,
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
        $dados = $request->validated();

        if (! empty($dados['senha'])) {
            $dados['senha'] = Hash::make($dados['senha']);
        } else {
            unset($dados['senha']);
        }

        if (
            $usuario->isAdministrador()
            && isset($dados['perfil'])
            && $dados['perfil'] !== Usuario::PERFIL_ADMINISTRADOR
            && $this->ehUltimoAdministradorAtivo($usuario)
        ) {
            return response()->json([
                'message' => 'Não é possível alterar o perfil do último administrador ativo.',
            ], 422);
        }

        if (
            $usuario->isAdministrador()
            && array_key_exists('status', $dados)
            && $dados['status'] === false
            && $this->ehUltimoAdministradorAtivo($usuario)
        ) {
            return response()->json([
                'message' => 'Não é possível inativar o último administrador ativo.',
            ], 422);
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

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    public function destroy(Request $request, Usuario $usuario): JsonResponse
    {
        if ($request->user()->id === $usuario->id) {
            return response()->json([
                'message' => 'Você não pode excluir o próprio usuário.',
            ], 422);
        }

        if ($usuario->isAdministrador() && $this->ehUltimoAdministradorAtivo($usuario)) {
            return response()->json([
                'message' => 'Não é possível excluir o último administrador ativo.',
            ], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        $this->auditoria->registrarModelo(CadastroAuditoriaService::ACAO_EXCLUIR, $usuario);

        return response()->json([
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }

    private function ehUltimoAdministradorAtivo(Usuario $usuario): bool
    {
        return Usuario::query()
            ->where('perfil', Usuario::PERFIL_ADMINISTRADOR)
            ->where('status', true)
            ->where('id', '!=', $usuario->id)
            ->doesntExist();
    }
}
