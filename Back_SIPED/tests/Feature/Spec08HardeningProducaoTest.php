<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\TruncarDadosOperacionaisSeeder;
use Database\Seeders\UsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * SPEC 08 — Hardening e checklist de produção.
 */
class Spec08HardeningProducaoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil = Usuario::PERFIL_ADMINISTRADOR): Usuario
    {
        return Usuario::create([
            'nome' => 'Usuário Spec 08', 'email' => 'spec08-'.uniqid().'@teste.com', 'senha' => Hash::make('senha-forte-123'),
            'cpf' => '52998224725', 'perfil' => $perfil, 'status' => true,
            'unidade' => 'Asa Norte', 'area' => 'CPED', 'telefone' => '61999990808',
        ]);
    }

    public function test_respostas_trazem_cabecalhos_de_seguranca(): void
    {
        $this->getJson('/api/user')->assertStatus(401)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_erro_interno_da_api_vem_em_json_sem_stack_trace(): void
    {
        config(['app.debug' => false]);
        Route::middleware('api')->get('/api/__spec08-erro', fn () => throw new RuntimeException('segredo interno'));

        $resposta = $this->get('/api/__spec08-erro')->assertStatus(500);
        $this->assertSame('Server Error', $resposta->json('message'));
        $this->assertStringNotContainsString('segredo interno', $resposta->getContent());
        $this->assertArrayNotHasKey('trace', $resposta->json());
    }

    public function test_https_obrigatorio_quando_ligado(): void
    {
        config(['producao.forcar_https' => true]);

        $this->get('/api/user')->assertRedirect()->assertStatus(301);
        $this->assertStringStartsWith('https://', $this->get('/api/user')->headers->get('Location'));
        $this->postJson('/api/login', ['email' => 'a@b.com', 'senha' => 'x'])->assertStatus(403);
        $this->get('/up')->assertOk();
    }

    public function test_operacoes_pesadas_tem_limite_por_usuario(): void
    {
        config(['producao.limites.pesado_por_minuto' => 2]);
        $this->actingAs($this->usuario(), 'sanctum');

        $this->postJson('/api/documentos', [])->assertStatus(422);
        $this->postJson('/api/documentos', [])->assertStatus(422);
        $this->postJson('/api/documentos', [])->assertStatus(429)
            ->assertJsonPath('message', 'Muitas operações em sequência. Aguarde um minuto e tente de novo.');
        // Consultas comuns continuam liberadas.
        $this->getJson('/api/documentos')->assertOk();
    }

    public function test_seeders_de_demonstracao_nao_rodam_em_producao(): void
    {
        $this->app['env'] = 'production';

        foreach ([UsuarioSeeder::class, TruncarDadosOperacionaisSeeder::class] as $seeder) {
            try {
                $this->app->make($seeder)->run();
                $this->fail("{$seeder} rodou em produção.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('não roda em produção', $e->getMessage());
            }
        }
        $this->assertSame(0, Usuario::count());
    }

    public function test_verificar_producao_aponta_no_go_em_ambiente_inseguro(): void
    {
        $this->seed(UsuarioSeeder::class);
        config(['app.debug' => true, 'app.url' => 'http://localhost', 'producao.forcar_https' => false]);

        $this->artisan('siped:verificar-producao')
            ->expectsOutputToContain('NO-GO')
            ->assertExitCode(1);

        $this->artisan('siped:verificar-producao --json')->assertExitCode(1);
    }

    public function test_verificar_producao_lista_usuarios_demo_com_senha_conhecida(): void
    {
        $this->seed(UsuarioSeeder::class);
        Usuario::where('email', 'editor@df.senac.br')->update(['senha' => Hash::make('outra-senha-forte')]);

        $this->artisan('siped:verificar-producao')
            ->expectsOutputToContain('root@df.senac.br')
            ->assertExitCode(1);
    }

    public function test_verificar_producao_avisa_quando_ha_um_so_root(): void
    {
        $this->seed(UsuarioSeeder::class);
        Usuario::where('email', 'root.substituto@df.senac.br')->update(['status' => false]);

        $this->artisan('siped:verificar-producao')
            ->expectsOutputToContain('Há só 1 Root ativo')
            ->assertExitCode(1);
    }

    public function test_backup_sqlite_gera_arquivo_e_remove_antigos(): void
    {
        $pasta = storage_path('framework/testing/backups-spec08');
        File::deleteDirectory($pasta);
        File::ensureDirectoryExists($pasta);
        $banco = $pasta.'/origem.sqlite';
        File::put($banco, 'conteudo-sqlite');
        File::put($pasta.'/siped-antigo.sql', 'velho');
        touch($pasta.'/siped-antigo.sql', now()->subDays(40)->getTimestamp());

        config(['database.connections.backup_teste' => ['driver' => 'sqlite', 'database' => $banco, 'prefix' => '']]);

        $this->artisan('siped:backup', ['--pasta' => $pasta, '--conexao' => 'backup_teste'])->assertExitCode(0);

        $gerados = collect(File::files($pasta))->map->getFilename()->filter(fn ($n) => str_starts_with($n, 'siped-'));
        $this->assertCount(1, $gerados);
        $this->assertStringEndsWith('.sqlite', $gerados->first());
        File::deleteDirectory($pasta);
    }

    public function test_cors_aceita_origens_do_env(): void
    {
        $this->assertNotEmpty(config('cors.allowed_origins'));
        $this->assertContains('http://127.0.0.1:5173', config('cors.allowed_origins'));
    }
}
