<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CursoExecucaoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Acompanhamento',
            'email' => 'editor-acompanhamento@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678121',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991021',
        ]);
    }

    private function cursoCatalogo(array $extra = []): Curso
    {
        $ciclo = PortfolioCiclo::atual();

        return Curso::create(array_merge([
            'titulo' => 'Técnico em Enfermagem',
            'status' => 'ATIVO',
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'ciclo_id' => $ciclo?->id,
        ], $extra));
    }

    public function test_cria_acompanhamento_herdando_eixo_do_curso(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $curso = $this->cursoCatalogo();

        $this->assertEscritaExternaBloqueada($this->postJson('/api/curso-execucoes', [
            'curso_id' => $curso->id,
            'codigo' => 'ACOMP-1',
            'turmas' => '2',
            'alunos' => '30',
            'status' => 'Em andamento',
        ]));
        $this->assertDatabaseCount('cursos', 1);
        $this->assertDatabaseCount('curso_por_eixos', 0);
    }

    public function test_nao_cria_acompanhamento_sem_curso_oficial(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->assertEscritaExternaBloqueada($this->postJson('/api/curso-execucoes', [
            'curso' => 'Curso solto',
            'turmas' => '1',
            'alunos' => '10',
        ]));
    }

    public function test_editar_importado_atualiza_indicadores(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $curso = $this->cursoCatalogo();
        $registro = CursoExecucao::create([
            'curso' => 'Técnico em Enfermagem',
            'curso_id' => $curso->id,
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'codigo' => 'IMP-1',
            'turmas' => '1',
            'alunos' => '10',
            'status' => 'Ativo',
        ]);

        $this->assertEscritaExternaBloqueada($this->putJson('/api/curso-execucoes/'.$registro->id, [
            'curso_id' => $curso->id,
            'turmas' => '4',
            'alunos' => '70',
            'status' => 'Concluído',
        ]));

        $card = collect($this->getJson('/api/eixos/resumo')->json('data.eixos'))
            ->firstWhere('nome', 'Ambiente e Saúde');
        $this->assertSame(1, $card['turmas']);
        $this->assertSame(10, $card['alunos']);
    }

    public function test_vincular_pendencia_atualiza_curso_id_sem_criar_curso(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $curso = $this->cursoCatalogo(['titulo' => 'Açougueiro']);
        $pendencia = CursoExecucao::create([
            'curso' => 'Acougueiro',
            'curso_id' => null,
            'eixo' => 'Gastronomia e Turismo',
            'codigo' => 'PEND-1',
            'turmas' => '2',
            'alunos' => '18',
            'status' => 'Ativo',
        ]);

        $this->assertEscritaExternaBloqueada($this->postJson('/api/curso-execucoes/'.$pendencia->id.'/vincular', [
            'curso_id' => $curso->id,
        ]));

        $this->assertDatabaseCount('cursos', 1);
        $this->assertSame(1, $this->getJson('/api/revisao-dados?tipo=sem_vinculo')->json('totais.sem_vinculo'));
    }

    public function test_rota_antiga_curso_por_eixos_continua_disponivel(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/curso-por-eixos')->assertOk();
        $this->getJson('/api/curso-execucoes')->assertOk();
    }
}
