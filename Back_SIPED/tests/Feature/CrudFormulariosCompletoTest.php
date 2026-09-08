<?php

namespace Tests\Feature;

use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cobertura complementar de CRUD e normalização de máscaras nos formulários
 * que ainda não tinham teste completo (list/create/show/update/delete).
 */
class CrudFormulariosCompletoTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor CRUD',
            'email' => 'editor-crud@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '52998224725',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990099',
        ]);
    }

    private function admin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin CRUD',
            'email' => 'admin-crud@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '11144477735',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990098',
        ]);
    }

    public function test_termo_referencia_crud_completo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/termos-referencia')->assertOk();

        $payload = [
            'nome' => 'TR CRUD completo',
            'eixo' => 'Gastronomia e Turismo',
            'processo_sei' => '2026.00.00010-0',
            'prazo_deadline' => '2026-12-01',
            'status' => 'Planejamento',
        ];

        $create = $this->postJson('/api/termos-referencia', $payload);
        $create->assertCreated();
        $id = $create->json('termo.id');

        $this->getJson("/api/termos-referencia/{$id}")
            ->assertOk()
            ->assertJsonPath('termo.nome', 'TR CRUD completo');

        $this->putJson("/api/termos-referencia/{$id}", [
            ...$payload,
            'nome' => 'TR CRUD atualizado',
            'status' => 'Em Andamento',
        ])
            ->assertOk()
            ->assertJsonPath('termo.nome', 'TR CRUD atualizado');

        $this->deleteJson("/api/termos-referencia/{$id}")->assertOk();
        $this->assertDatabaseMissing('termos_referencia', ['id' => $id]);
    }

    public function test_portfolio_ciclo_show_update_e_delete(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $create = $this->postJson('/api/portfolio-ciclos', [
            'nome' => '2030',
            'observacao' => 'Ciclo de teste CRUD',
            'atual' => false,
        ]);
        $create->assertCreated();
        $id = $create->json('ciclo.id');

        $this->getJson("/api/portfolio-ciclos/{$id}")
            ->assertOk()
            ->assertJsonPath('ciclo.nome', '2030')
            ->assertJsonPath('ciclo.observacao', 'Ciclo de teste CRUD');

        $this->putJson("/api/portfolio-ciclos/{$id}", [
            'nome' => '2030-A',
            'observacao' => 'Observação atualizada',
        ])
            ->assertOk()
            ->assertJsonPath('ciclo.nome', '2030-A');

        $this->deleteJson("/api/portfolio-ciclos/{$id}")->assertOk();
        $this->assertDatabaseMissing('portfolio_ciclos', ['id' => $id]);
    }

    public function test_portfolio_ciclo_atual_nao_pode_ser_excluido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $atual = PortfolioCiclo::atual();
        $this->assertNotNull($atual);

        $this->deleteJson("/api/portfolio-ciclos/{$atual->id}")
            ->assertStatus(422);
    }

    public function test_jornada_pedagogica_show_por_id(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $create = $this->postJson('/api/jornadas-pedagogicas', [
            'titulo' => 'Jornada show teste',
            'status' => 'Rascunho',
            'tem_pre_jornada' => 'Não',
        ]);
        $create->assertCreated();
        $id = $create->json('jornada.id');

        $this->getJson("/api/jornadas-pedagogicas/{$id}")
            ->assertOk()
            ->assertJsonPath('jornada.titulo', 'Jornada show teste');

        $this->deleteJson("/api/jornadas-pedagogicas/{$id}")->assertOk();
    }

    public function test_mascaras_cpf_telefone_e_sei_sao_normalizadas_no_cadastro(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $usuario = $this->postJson('/api/usuarios', [
            'nome' => 'Usuário Máscara',
            'email' => 'mascara@teste.com',
            'senha' => 'senha123',
            'cpf' => '390.533.447-05',
            'perfil' => Usuario::PERFIL_EDITOR,
            'telefone' => '(61) 99999-0088',
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
        ]);
        $usuario->assertCreated();
        $usuario->assertJsonPath('usuario.cpf', '39053344705');
        $usuario->assertJsonPath('usuario.telefone', '61999990088');

        $editor = $this->editor();
        $this->actingAs($editor, 'sanctum');

        $visita = $this->postJson('/api/visitas-tecnicas', [
            'unidade' => 'Asa Norte',
            'eixo' => 'Gastronomia',
            'processo_sei' => ' 2026.000011111-11 ',
            'data_solicitacao' => '2026-07-01',
            'data_visita_prevista' => '2026-07-10',
            'prazo_limite' => '2026-07-20',
            'status' => 'Pendente',
            'responsavel' => 'Equipe',
        ]);
        $visita->assertCreated();
        $visita->assertJsonPath('visitaTecnica.processo_sei', '2026.000011111-11');

        $curso = $this->postJson('/api/cursos', [
            'titulo' => 'Curso máscara SEI',
            'eixo' => 'Gastronomia e Turismo',
            'modalidade' => 'Presencial',
            'tipo' => 'Livre',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-MASK-001',
            'processo_sei' => 'SEI#2026.00001-1',
            'carga_horaria' => '160',
            'unidades_oferta' => ['Asa Norte'],
        ]);
        $curso->assertCreated();
        $curso->assertJsonPath('curso.processo_sei', '2026.00001-1');
        $curso->assertJsonPath('curso.carga_horaria', '160');
    }

    public function test_todos_modulos_crud_principais_estao_acessiveis(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $endpoints = [
            '/api/cursos',
            '/api/plano-de-metas',
            '/api/resolucoes',
            '/api/termos-referencia',
            '/api/pcas',
            '/api/curso-por-eixos',
            '/api/horas-pedagogicas',
            '/api/visitas-tecnicas',
            '/api/acoes-extensivas',
            '/api/eventos',
            '/api/jornadas-pedagogicas',
            '/api/cped-equipes',
            '/api/portfolio-ciclos',
            '/api/kanban/quadros',
            '/api/fluxogramas',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)->assertOk();
        }

        $this->actingAs($this->admin(), 'sanctum');
        $this->getJson('/api/usuarios')->assertOk();
    }
}
