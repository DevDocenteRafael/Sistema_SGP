<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\PortfolioCiclo;
use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CursoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Cursos',
            'email' => 'editor-cursos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678001',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990001',
        ]);
    }

    /** Payload mínimo válido para o cadastro completo de Curso (todos os campos obrigatórios do formulário). */
    private function payloadValido(array $sobrescreve = []): array
    {
        $ciclo = PortfolioCiclo::query()->first() ?? PortfolioCiclo::create(['nome' => '2025-2026', 'atual' => true]);
        $regiao = RegiaoAdministrativa::query()->firstOrCreate(['nome' => 'Asa Norte'], ['ativo' => true]);
        UnidadeOferta::query()->firstOrCreate(
            ['nome' => 'Asa Norte'],
            ['tipo' => UnidadeOferta::TIPO_UNIDADE, 'ativo' => true, 'regiao_administrativa_id' => $regiao->id],
        );

        return array_merge([
            'ciclo_id' => $ciclo->id,
            'titulo' => 'Curso de teste API',
            'eixo' => 'Gastronomia e Turismo',
            'segmento' => 'Gastronomia',
            'programa' => '60+',
            'modalidade' => 'Qualificação Profissional',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-TEST-001',
            'codigo_dn' => 'DN-TEST-001',
            'carga_horaria' => '40',
            'turmas' => '1',
            'codigo_processo' => 'PROC-TEST-001',
            'alunos' => '10',
            'instrutor' => 'Instrutor Teste',
            'descricao' => 'Descrição de teste.',
            'identificacao' => '2026',
            'ultima_revisao' => '2026',
            'processo_sei' => '123.456/2026-01',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'unidade' => 'Asa Norte',
            'unidades_oferta' => ['Asa Norte'],
            'observacoes' => 'Observações de teste.',
            'valores' => '0',
            'compativel_bolsa' => 'NÃO',
            'comercial' => 'NÃO',
            'pcn' => 'PCN de teste.',
            'pcr' => 'PCR de teste.',
        ], $sobrescreve);
    }

    public function test_can_list_create_show_update_and_delete_curso(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/cursos')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'eixos', 'status', 'modalidades', 'sim_nao'],
            ]);
        $this->assertSame(config('eixos'), $this->getJson('/api/cursos')->json('meta.eixos'));

        $payload = $this->payloadValido();

        $create = $this->postJson('/api/cursos', $payload);
        $create->assertCreated();
        $create->assertJsonPath('curso.titulo', 'Curso de teste API');
        $create->assertJsonPath('curso.status', 'ATIVO');

        $id = $create->json('curso.id');
        $this->assertNotNull($id);

        $this->getJson("/api/cursos/{$id}")
            ->assertOk()
            ->assertJsonPath('curso.codigo_sig', 'SIG-TEST-001');

        $this->putJson("/api/cursos/{$id}", [
            ...$payload,
            'titulo' => 'Curso atualizado',
            'status' => 'INATIVO',
        ])
            ->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso atualizado')
            ->assertJsonPath('curso.status', 'INATIVO');

        $this->deleteJson("/api/cursos/{$id}")->assertOk();
        $this->assertDatabaseMissing('cursos', ['id' => $id]);
    }

    public function test_filters_cursos_by_status_and_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create([
            'titulo' => 'Curso Saúde',
            'eixo' => 'Saúde',
            'status' => 'ATIVO',
        ]);
        Curso::create([
            'titulo' => 'Curso Inativo',
            'eixo' => 'Gastronomia e Turismo',
            'status' => 'INATIVO',
        ]);

        $filtered = $this->getJson('/api/cursos?status=ATIVO&eixo=Saúde');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.titulo', 'Curso Saúde');

        $porCanonico = $this->getJson('/api/cursos?status=ATIVO&eixo=Ambiente e Saúde');
        $porCanonico->assertOk();
        $porCanonico->assertJsonPath('meta.total', 1);
        $porCanonico->assertJsonPath('data.0.titulo', 'Curso Saúde');
    }

    public function test_canonicaliza_alias_saude_e_rejeita_eixo_invalido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $segmentoPorEixo = [
            'Gastronomia e Turismo' => 'Gastronomia',
            'Tecnologia e Economia Criativa' => 'Tecnologia da Informação - Desenvolvimento',
            'Gestão e Moda' => 'Gestão e Comércio',
            'Beleza e Cuidado Pessoal' => 'Beleza e cuidado pessoal',
            'Ambiente e Saúde' => 'Enfermagem',
        ];

        foreach (array_values(config('eixos')) as $indice => $eixo) {
            $this->postJson('/api/cursos', $this->payloadValido([
                'titulo' => 'Curso '.$eixo,
                'eixo' => $eixo,
                'segmento' => $segmentoPorEixo[$eixo],
                'modalidade' => 'Aperfeiçoamento',
                'status' => 'ATIVO',
                'codigo_sig' => 'SIG-EIXO-'.substr(md5($eixo), 0, 6),
                'processo_sei' => sprintf('123.%03d/2026-01', $indice),
                'carga_horaria' => '40',
            ]))->assertCreated()->assertJsonPath('curso.eixo', $eixo);
        }

        $alias = $this->postJson('/api/cursos', $this->payloadValido([
            'titulo' => 'Curso alias Saúde',
            'eixo' => 'Saúde',
            'segmento' => 'Enfermagem',
            'modalidade' => 'Oficina',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-ALIAS-SAUDE',
            'processo_sei' => '123.999/2026-01',
            'carga_horaria' => '40',
        ]));
        $alias->assertCreated();
        $alias->assertJsonPath('curso.eixo', 'Ambiente e Saúde');

        $this->postJson('/api/cursos', $this->payloadValido([
            'titulo' => 'Curso eixo inválido',
            'eixo' => '60+',
            'segmento' => 'Enfermagem',
            'modalidade' => 'Oficina',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-INVALID-EIXO',
            'carga_horaria' => '40',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eixo']);

        $this->postJson('/api/cursos', $this->payloadValido([
            'titulo' => 'Curso modalidade inválida',
            'eixo' => 'Gestão e Moda',
            'segmento' => 'Gestão e Comércio',
            'modalidade' => 'Presencial',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-INVALID-MOD',
            'carga_horaria' => '40',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['modalidade']);

        $meta = $this->getJson('/api/cursos')->json('meta.eixos');
        $this->assertSame(config('eixos'), $meta);
        $this->assertNotContains('Saúde', $meta);
        $this->assertNotContains('60+', $meta);
        $this->assertNotContains('Ensino Médio 2025', $meta);
    }

    public function test_guest_cannot_list_cursos(): void
    {
        $this->getJson('/api/cursos')->assertUnauthorized();
    }
}
