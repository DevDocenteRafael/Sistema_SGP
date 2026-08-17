<?php

namespace Tests\Feature;

use App\Models\Curso;
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
