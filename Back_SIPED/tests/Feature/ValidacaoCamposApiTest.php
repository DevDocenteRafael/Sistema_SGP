<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Garante que campos inválidos são rejeitados pelo back-end (422),
 * mesmo que o front-end seja contornado.
 */
class ValidacaoCamposApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Validação',
            'email' => 'editor-validacao@teste.com',
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
            'nome' => 'Admin Validação',
            'email' => 'admin-validacao@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '11144477735',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990098',
        ]);
    }

    public function test_usuario_rejeita_cpf_e_telefone_invalidos(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $this->postJson('/api/usuarios', [
            'nome' => 'Teste CPF',
            'email' => 'cpf-invalido@teste.com',
            'senha' => 'senha123',
            'cpf' => '111.111.111-11',
            'perfil' => Usuario::PERFIL_EDITOR,
            'telefone' => '123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpf', 'telefone']);
    }

    public function test_visita_tecnica_rejeita_processo_sei_invalido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/visitas-tecnicas', [
            'unidade' => 'Asa Norte',
            'eixo' => 'Gastronomia',
            'processo_sei' => '@@@',
            'data_solicitacao' => '2026-07-01',
            'data_visita_prevista' => '2026-07-10',
            'prazo_limite' => '2026-07-20',
            'status' => 'Pendente',
            'responsavel' => 'Equipe',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['processo_sei']);
    }

    public function test_visita_tecnica_rejeita_datas_invertidas(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/visitas-tecnicas', [
            'unidade' => 'Asa Norte',
            'eixo' => 'Gastronomia',
            'processo_sei' => '2026.000011111-11',
            'data_solicitacao' => '2026-07-10',
            'data_visita_prevista' => '2026-07-01',
            'prazo_limite' => '2026-07-20',
            'status' => 'Pendente',
            'responsavel' => 'Equipe',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_visita_prevista']);
    }

    public function test_curso_rejeita_carga_horaria_invalida(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/cursos', [
            'titulo' => 'Curso teste validação',
            'eixo' => 'Gastronomia e Turismo',
            'modalidade' => 'Presencial',
            'tipo' => 'Livre',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-VAL-001',
            'carga_horaria' => '800h',
            'unidades_oferta' => ['Asa Norte'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['carga_horaria']);
    }

    public function test_curso_rejeita_processo_sei_invalido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/cursos', [
            'titulo' => 'Curso teste validação SEI',
            'eixo' => 'Gastronomia e Turismo',
            'modalidade' => 'Presencial',
            'tipo' => 'Livre',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-VAL-003',
            'processo_sei' => '@@@',
            'unidades_oferta' => ['Asa Norte'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['processo_sei']);
    }

    public function test_hora_pedagogica_rejeita_matricula_e_processo_sei_invalidos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/horas-pedagogicas', [
            'matricula' => 'ABC',
            'pessoa' => 'Fulano',
            'segmento' => 'Gastronomia',
            'eixo' => 'Gastronomia',
            'processo_sei' => 'SEI@invalido',
            'ano' => 2026,
            'motivo' => 'Teste',
            'status' => 'Pendente',
            'ativo' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matricula', 'processo_sei']);
    }

    public function test_plano_de_meta_rejeita_numero_sei_invalido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/plano-de-metas', [
            'segmento' => 'Infraestrutura',
            'curso' => 'Curso teste',
            'tipo' => 'QUALIFICAÇÃO',
            'numero_sei' => '@@@',
            'codigo_sig' => 'SIG-VAL-002',
            'mes_entrega' => 'Janeiro',
            'status' => 'PLANEJADO',
            'status_final' => 'PENDENTE',
            'ano' => 2026,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero_sei']);
    }

    public function test_acao_extensiva_rejeita_processo_sei_invalido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/acoes-extensivas', [
            'priorizacao' => 'Alta',
            'atribuido' => 'ana.teste',
            'eixo' => 'Gastronomia e Turismo',
            'numero_processo_sei' => 'proc@invalido',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Assunto teste',
            'status' => 'CPED',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero_processo_sei']);
    }

    public function test_evento_rejeita_acao_vinculada_obrigatoria_e_quantidade_negativa(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/eventos', [
            'nome' => 'Evento teste',
            'ano' => '2026',
            'data' => '2026-08-01',
            'unidade' => 'Sobradinho',
            'eixo' => 'Gastronomia',
            'possui_acao_extensiva' => 'Sim',
            'acao_vinculada' => '',
            'status' => 'Planejado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['acao_vinculada']);

        $this->postJson('/api/eventos', [
            'nome' => 'Evento teste 2',
            'ano' => '2026',
            'data' => '2026-08-01',
            'unidade' => 'Sobradinho',
            'eixo' => 'Gastronomia',
            'possui_acao_extensiva' => 'Não',
            'status' => 'Planejado',
            'quantidade_pessoas' => -5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantidade_pessoas']);
    }

    public function test_resolucao_rejeita_resumo_acima_do_limite(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/resolucoes', [
            'numero' => 'MEC/2026/VAL',
            'resumo' => Str::repeat('A', 1001),
            'data_inicio_vigencia' => '2026-08-01',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['resumo']);
    }

    public function test_kanban_rejeita_quadro_coluna_e_cartao_invalidos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/kanban/quadros', ['nome' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);

        $createQuadro = $this->postJson('/api/kanban/quadros', ['nome' => 'Quadro Validação']);
        $createQuadro->assertCreated();
        $slug = $createQuadro->json('kanban_quadro.slug');

        $this->postJson("/api/kanban/quadros/{$slug}/colunas", ['titulo' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo']);

        $this->postJson("/api/kanban/quadros/{$slug}/cartoes", [
            'coluna_titulo' => 'Backlog',
            'titulo' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo']);
    }

    public function test_fluxograma_rejeita_titulo_vazio_e_descricao_longa(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/fluxogramas', ['titulo' => '', 'tipo' => 'linear'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo']);

        $this->postJson('/api/fluxogramas', [
            'titulo' => 'Fluxo teste',
            'descricao' => Str::repeat('X', 2001),
            'tipo' => 'linear',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['descricao']);
    }

    public function test_curso_por_eixo_rejeita_turmas_com_letras(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/curso-por-eixos', [
            'curso' => 'Curso Eixo',
            'eixo' => 'Gastronomia',
            'ano' => '2026',
            'status' => 'Ativo',
            'turmas' => '2a',
            'alunos' => '10x',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['turmas', 'alunos']);
    }

    public function test_termo_referencia_rejeita_processo_sei_invalido_e_nome_longo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/termos-referencia', [
            'nome' => Str::repeat('T', 256),
            'eixo' => 'Gastronomia',
            'processo_sei' => '@@@',
            'prazo_deadline' => '2026-12-01',
            'status' => 'Planejamento',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'processo_sei']);
    }

    public function test_pca_rejeita_carga_horaria_e_numero_sei_invalidos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/pcas', [
            'titulo' => 'PCA teste',
            'status' => 'Vigente',
            'numero_sei' => '@@@',
            'carga_horaria' => 'abc',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero_sei', 'carga_horaria']);
    }

    public function test_portfolio_ciclo_rejeita_nome_longo_e_observacao_longa(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/portfolio-ciclos', [
            'nome' => Str::repeat('X', 81),
            'observacao' => Str::repeat('O', 2001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'observacao']);
    }

    public function test_jornada_rejeita_titulo_longo_e_pre_jornada_sem_data(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/jornadas-pedagogicas', [
            'titulo' => Str::repeat('J', 256),
            'status' => 'Rascunho',
            'tem_pre_jornada' => 'Sim',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo', 'data_pre_jornada']);
    }

    public function test_cped_rejeita_email_invalido_e_nome_longo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/cped-equipes', [
            'nome' => Str::repeat('N', 101),
            'cargo' => 'Instrutor',
            'setor' => 'Gastronomia',
            'contato' => 'email-invalido',
            'tipo' => 'instrutor',
            'eixo_vinculado' => 'Gastronomia',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'contato']);
    }

    public function test_usuario_rejeita_nome_longo(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $this->postJson('/api/usuarios', [
            'nome' => Str::repeat('U', 101),
            'email' => 'nome-longo@teste.com',
            'senha' => 'senha123',
            'cpf' => '52998224725',
            'perfil' => Usuario::PERFIL_EDITOR,
            'telefone' => '61999990099',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }
}
