<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SPEC 01 — 1.3: notificações internas respeitam o escopo de eixos do usuário.
 */
class NotificacaoEscopoEixoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-05'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $perfil, ?array $eixos, string $sufixo): Usuario
    {
        $cpfs = ['adm' => '11144477735', 'sau' => '52998224725', 'sem' => '39053344705'];

        return Usuario::create([
            'nome' => 'Usuário '.$sufixo,
            'email' => "escopo-{$sufixo}@teste.com",
            'senha' => Hash::make('senha123'),
            'cpf' => $cpfs[$sufixo],
            'perfil' => $perfil,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990'.str_pad((string) strlen($sufixo), 3, '0', STR_PAD_LEFT),
            'eixos' => $eixos,
        ]);
    }

    private function cenario(): void
    {
        Resolucao::create([
            'numero' => 'RES-GERAL',
            'curso_relacionado' => 'Curso',
            'resumo' => 'Resolução sem eixo (geral).',
            'status' => 'vigente',
            'data_inicio_vigencia' => '2021-09-01',
            'data_fim_vigencia' => '2026-09-01',
        ]);

        foreach (['Ambiente e Saúde' => 'TR-SAUDE', 'Gestão e Moda' => 'TR-GESTAO'] as $eixo => $nome) {
            TermoReferencia::create([
                'nome' => $nome,
                'eixo' => $eixo,
                'processo_sei' => '123.001/2026-01',
                'prazo_deadline' => '2026-10-15',
                'status' => 'Em Andamento',
            ]);
        }
    }

    private function titulos(): array
    {
        return collect($this->getJson('/api/notificacoes')->assertOk()->json('itens'))->pluck('titulo')->sort()->values()->all();
    }

    public function test_coordenacao_ve_tudo(): void
    {
        $this->cenario();
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR, ['Gestão e Moda'], 'adm'), 'sanctum');

        $this->assertSame(['RES-GERAL', 'TR-GESTAO', 'TR-SAUDE'], $this->titulos());
        $this->getJson('/api/notificacoes')->assertJsonPath('meta.escopo.todos', true);
    }

    public function test_usuario_operacional_ve_so_o_seu_eixo_e_os_itens_gerais(): void
    {
        $this->cenario();
        $this->actingAs($this->usuario(Usuario::PERFIL_EDITOR, ['Saúde'], 'sau'), 'sanctum');

        $this->assertSame(['RES-GERAL', 'TR-SAUDE'], $this->titulos());
        $this->getJson('/api/notificacoes')
            ->assertJsonPath('meta.escopo.todos', false)
            ->assertJsonPath('meta.escopo.eixos', ['Ambiente e Saúde']);
    }

    public function test_usuario_sem_eixo_associado_continua_vendo_tudo(): void
    {
        $this->cenario();
        $this->actingAs($this->usuario(Usuario::PERFIL_CONSULTOR, null, 'sem'), 'sanctum');

        $this->assertSame(['RES-GERAL', 'TR-GESTAO', 'TR-SAUDE'], $this->titulos());
    }

    public function test_notificacoes_tem_somente_atencao_e_vencido(): void
    {
        $this->cenario();
        $ciclo = Ciclo::query()->first() ?? Ciclo::create(['nome' => '2025-2026', 'atual' => true]);
        VisitaTecnica::create([
            'ciclo_id' => $ciclo->id,
            'unidade' => 'Asa Norte',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '123.002/2026-01',
            'prazo_limite' => '2026-10-05', // vence hoje: antes era "crítico"
            'status' => 'Pendente',
        ]);
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR, null, 'adm'), 'sanctum');

        $resposta = $this->getJson('/api/notificacoes')->assertOk();
        $niveis = collect($resposta->json('itens'))->pluck('nivel')->unique()->sort()->values()->all();
        $this->assertSame(['atencao', 'vencido'], $niveis);
        $this->assertArrayNotHasKey('critico', $resposta->json('meta.por_nivel'));
        $this->assertSame('atencao', collect($resposta->json('itens'))->firstWhere('modulo', 'visitas-tecnicas')['nivel']);
    }

    public function test_admin_associa_eixos_ao_usuario(): void
    {
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR, null, 'adm'), 'sanctum');
        $alvo = $this->usuario(Usuario::PERFIL_EDITOR, null, 'sau');

        $payload = [
            'nome' => $alvo->nome,
            'email' => $alvo->email,
            'cpf' => $alvo->cpf,
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990003',
        ];

        // Multipart (formulário com foto) envia os eixos como JSON.
        $this->post('/api/usuarios/'.$alvo->id, [...$payload, '_method' => 'PUT', 'eixos' => json_encode(['Saúde', 'Gestão e Moda'])], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('usuario.eixos', ['Ambiente e Saúde', 'Gestão e Moda']);

        $this->putJson('/api/usuarios/'.$alvo->id, [...$payload, 'eixos' => ['Eixo inventado']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eixos.0']);

        $this->putJson('/api/usuarios/'.$alvo->id, [...$payload, 'eixos' => []])
            ->assertOk()
            ->assertJsonPath('usuario.eixos', []);
    }
}
