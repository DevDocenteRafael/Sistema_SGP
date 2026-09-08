<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(array $extra = []): Usuario
    {
        return Usuario::create(array_merge([
            'nome' => 'Editor Segurança',
            'email' => 'editor-seg@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678931',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '11999999931',
        ], $extra));
    }

    private function criarAdmin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin Segurança',
            'email' => 'admin-seg@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678932',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '11999999932',
        ]);
    }

    public function test_login_ok_retorna_token(): void
    {
        $this->criarUsuario();

        $response = $this->postJson('/api/login', [
            'email' => 'editor-seg@teste.com',
            'senha' => 'senha123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'usuario' => ['id', 'nome', 'email', 'perfil']]);
    }

    public function test_login_dos_usuarios_seed_demo_retorna_200(): void
    {
        $this->seed(\Database\Seeders\UsuarioSeeder::class);

        foreach ([
            ['email' => 'administrador@df.senac.br', 'senha' => 'senac2025', 'perfil' => Usuario::PERFIL_ADMINISTRADOR],
            ['email' => 'editor@df.senac.br', 'senha' => 'editor2025', 'perfil' => Usuario::PERFIL_EDITOR],
            ['email' => 'consultor@df.senac.br', 'senha' => 'consultor2025', 'perfil' => Usuario::PERFIL_CONSULTOR],
        ] as $credencial) {
            $response = $this->postJson('/api/login', [
                'email' => $credencial['email'],
                'senha' => $credencial['senha'],
            ]);

            $response->assertOk();
            $response->assertJsonPath('usuario.email', $credencial['email']);
            $response->assertJsonPath('usuario.perfil', $credencial['perfil']);
            $this->assertNotSame(403, $response->status());
        }
    }

    public function test_login_inativo_e_bloqueado(): void
    {
        $this->criarUsuario(['status' => false, 'email' => 'inativo@teste.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'inativo@teste.com',
            'senha' => 'senha123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_e_limitado_por_throttle(): void
    {
        RateLimiter::clear('login');

        $this->criarUsuario(['email' => 'throttle@teste.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'throttle@teste.com',
                'senha' => 'errada',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'throttle@teste.com',
            'senha' => 'errada',
        ])->assertStatus(429);
    }

    public function test_usuario_inativo_perde_acesso_com_token_existente(): void
    {
        $usuario = $this->criarUsuario(['email' => 'token-inativo@teste.com']);
        $token = $usuario->createToken('sgp-api')->plainTextToken;

        $usuario->update(['status' => false]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_inativar_usuario_revoga_tokens(): void
    {
        $admin = $this->criarAdmin();
        $alvo = $this->criarUsuario(['email' => 'revogar@teste.com', 'cpf' => '52998224725']);
        $alvo->createToken('sgp-api');

        $this->assertSame(1, $alvo->tokens()->count());

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/usuarios/'.$alvo->id, [
                'nome' => $alvo->nome,
                'email' => $alvo->email,
                'perfil' => $alvo->perfil,
                'status' => false,
                'unidade' => $alvo->unidade,
                'area' => $alvo->area,
                'telefone' => $alvo->telefone,
                'cpf' => $alvo->cpf,
            ])
            ->assertOk();

        $this->assertSame(0, $alvo->fresh()->tokens()->count());
    }

    public function test_token_expirado_nao_autentica(): void
    {
        config(['sanctum.expiration' => 60]);

        $usuario = $this->criarUsuario(['email' => 'expira@teste.com', 'cpf' => '12345678934']);
        $plain = $usuario->createToken('sgp-api')->plainTextToken;

        /** @var PersonalAccessToken $token */
        $token = PersonalAccessToken::findToken($plain);
        $token->forceFill([
            'created_at' => now()->subMinutes(120),
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
