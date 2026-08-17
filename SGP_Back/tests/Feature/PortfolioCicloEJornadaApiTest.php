<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\PlanoDeMeta;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortfolioCicloEJornadaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Ciclos',
            'email' => 'editor-ciclos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678021',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990021',
        ]);
    }

    private function consultor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Consultor Ciclos',
            'email' => 'consultor-ciclos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678022',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990022',
        ]);
    }

    public function test_cria_curso_semelhante_somente_com_justificativa(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $payload = [
            'titulo' => 'Curso duplicado',
            'eixo' => 'Saúde',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-DUP-001',
            'processo_sei' => '2026.SEI.001',
        ];

        $this->postJson('/api/cursos', $payload)->assertCreated();

        $bloqueado = $this->postJson('/api/cursos', $payload);
        $bloqueado->assertStatus(409);
        $bloqueado->assertJsonPath('duplicidade', true);
        $bloqueado->assertJsonPath('exige_justificativa', true);

        $liberado = $this->postJson('/api/cursos', [
            ...$payload,
            'justificativa_duplicidade' => 'Turma extra autorizada pela coordenação.',
        ]);
        $liberado->assertCreated();
        $liberado->assertJsonPath('curso.justificativa_duplicidade', 'Turma extra autorizada pela coordenação.');
    }

    public function test_gerar_proximo_portfolio_copia_cursos_do_ciclo_origem(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $origem = PortfolioCiclo::atual();
        $this->assertNotNull($origem);

        Curso::create([
            'titulo' => 'Curso origem',
            'eixo' => 'Saúde',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-CICLO-1',
            'ciclo_id' => $origem->id,
        ]);

        $response = $this->postJson('/api/portfolio-ciclos/gerar-proximo', [
            'origem_id' => $origem->id,
            'nome' => '2028',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('ciclo.nome', '2028');
        $this->assertSame(1, (int) $response->json('ciclo.cursos_count'));

        $novoId = $response->json('ciclo.id');
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Curso origem',
            'codigo_sig' => 'SIG-CICLO-1',
            'ciclo_id' => $novoId,
        ]);
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Curso origem',
            'ciclo_id' => $origem->id,
        ]);

        $filtrado = $this->getJson('/api/cursos?ciclo_id='.$novoId);
        $filtrado->assertOk();
        $filtrado->assertJsonPath('meta.total', 1);
        $filtrado->assertJsonPath('data.0.titulo', 'Curso origem');
    }

    public function test_ciclo_novo_nao_herda_cursos_nem_metas_de_outro_ano(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $origem = PortfolioCiclo::atual();
        $this->assertNotNull($origem);

        Curso::create([
            'titulo' => 'Curso do ciclo atual',
            'eixo' => 'Saúde',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-CICLO-ATUAL',
            'ciclo_id' => $origem->id,
        ]);

        PlanoDeMeta::create([
            'segmento' => 'Pedagógico',
            'curso' => 'Meta 2026',
            'tipo' => 'QUALIFICAÇÃO',
            'numero_sei' => 'SEI-CICLO-01',
            'codigo_sig' => 'SIG-META-01',
            'mes_entrega' => 'Janeiro',
            'status' => 'PLANEJADO',
            'status_final' => 'PENDENTE',
            'ano' => 2026,
        ]);

        $response = $this->postJson('/api/portfolio-ciclos', [
            'nome' => '2027',
            'atual' => false,
        ]);

        $response->assertCreated();
        $this->assertSame(0, (int) $response->json('ciclo.cursos_count'));
        $this->assertSame(0, (int) $response->json('ciclo.composicao.plano_de_metas'));

        $novoId = $response->json('ciclo.id');

        $this->getJson('/api/cursos?ciclo_id='.$novoId)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/plano-de-metas?ciclo_id='.$novoId)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/plano-de-metas?ciclo_id='.$origem->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/portfolio-ciclos')
            ->assertOk();

        $ciclos = collect($this->getJson('/api/portfolio-ciclos')->json('data'));
        $this->assertSame(1, (int) $ciclos->firstWhere('id', $origem->id)['composicao']['plano_de_metas']);
        $this->assertSame(0, (int) $ciclos->firstWhere('id', (int) $novoId)['composicao']['plano_de_metas']);
    }

    public function test_lista_ciclos_inclui_anos_e_composicao_do_portfolio(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->getJson('/api/portfolio-ciclos');
        $response->assertOk();
        $response->assertJsonPath('data.0.nome', '2025-2026');
        $this->assertSame(['2025', '2026'], $response->json('data.0.anos'));
        $response->assertJsonStructure([
            'data' => [[
                'composicao' => [
                    'cursos',
                    'plano_de_metas',
                    'pca',
                    'eixos',
                ],
            ]],
        ]);
    }

    public function test_consultor_nao_gera_proximo_portfolio(): void
    {
        $this->actingAs($this->consultor(), 'sanctum');

        $this->postJson('/api/portfolio-ciclos/gerar-proximo', [
            'nome' => '2029',
        ])->assertForbidden();
    }

    public function test_jornada_pedagogica_crud_e_pdf(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/jornadas-pedagogicas')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'status']]);

        $create = $this->postJson('/api/jornadas-pedagogicas', [
            'titulo' => 'Jornada 2026',
            'status' => 'Rascunho',
            'tem_pre_jornada' => 'Sim',
            'data_inicio' => '2026-02-01',
            'data_fim' => '2026-02-03',
            'local' => 'Asa Norte',
            'espaco' => 'Auditório',
            'verba' => 'R$ 5.000,00',
            'programacao' => 'Abertura e oficinas',
        ]);
        $create->assertCreated();
        $id = $create->json('jornada.id');

        $this->putJson("/api/jornadas-pedagogicas/{$id}", [
            'titulo' => 'Jornada 2026 consolidada',
            'status' => 'Consolidado',
            'tem_pre_jornada' => 'Sim',
            'data_inicio' => '2026-02-01',
            'data_fim' => '2026-02-03',
            'local' => 'Asa Norte',
        ])->assertOk()->assertJsonPath('jornada.status', 'Consolidado');

        $pdf = $this->get("/api/jornadas-pedagogicas/{$id}/pdf");
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $this->deleteJson("/api/jornadas-pedagogicas/{$id}")->assertOk();
        $this->assertDatabaseMissing('jornadas_pedagogicas', ['id' => $id]);
    }

    public function test_consultor_nao_cria_jornada(): void
    {
        $this->actingAs($this->consultor(), 'sanctum');

        $this->postJson('/api/jornadas-pedagogicas', [
            'titulo' => 'Jornada consultor',
            'status' => 'Rascunho',
            'tem_pre_jornada' => 'Não',
        ])->assertForbidden();
    }

    public function test_sistemas_apoio_lista_links_sem_senha(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->getJson('/api/sistemas-apoio');
        $response->assertOk();
        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['sei', 'sig', 'sigin', 'senac'], $keys);

        foreach ($response->json('data') as $item) {
            $this->assertArrayHasKey('url', $item);
            $this->assertArrayNotHasKey('senha', $item);
            $this->assertArrayNotHasKey('password', $item);
        }
    }
}
