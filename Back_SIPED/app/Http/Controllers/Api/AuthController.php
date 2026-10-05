<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use App\Services\CadastroAuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly CadastroAuditoriaService $auditoria,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->senha, $usuario->senha)) {
            $this->registrarFalha($usuario, (string) $request->email, 'credenciais_invalidas');

            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        if (! $usuario->status) {
            $this->registrarFalha($usuario, (string) $request->email, 'usuario_inativo');

            throw ValidationException::withMessages([
                'email' => ['Usuário inativo. Entre em contato com o administrador.'],
            ]);
        }

        $token = $usuario->createToken('sgp-api')->plainTextToken;

        $this->auditoria->registrar(
            CadastroAuditoriaService::ACAO_LOGIN,
            'autenticacao',
            $usuario,
            'Entrou no sistema',
            null,
            $usuario,
        );

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'perfil' => $usuario->perfil,
                'unidade' => $usuario->unidade,
                'foto' => $usuario->foto,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->currentAccessToken()?->delete();

        $this->auditoria->registrar(
            CadastroAuditoriaService::ACAO_LOGOUT,
            'autenticacao',
            $usuario,
            'Saiu do sistema',
            null,
            $usuario,
        );

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Tentativa recusada: guarda o e-mail informado (nunca a senha), IP e user-agent.
     */
    private function registrarFalha(?Usuario $usuario, string $email, string $motivo): void
    {
        $this->auditoria->registrar(
            CadastroAuditoriaService::ACAO_LOGIN_FALHA,
            'autenticacao',
            $usuario,
            $motivo === 'usuario_inativo'
                ? 'Login recusado: usuário inativo'
                : 'Login recusado: e-mail ou senha inválidos',
            ['email' => mb_substr($email, 0, 150), 'motivo' => $motivo],
            $usuario,
        );
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => $request->user(),
        ]);
    }
}
