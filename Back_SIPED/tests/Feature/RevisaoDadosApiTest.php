<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RevisaoDadosApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Revisao',
            'email' => 'editor-revisao@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678131',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991031',
        ]);
    }

    public function test_lista_paginada_nao_usa_amostra(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $ciclo = PortfolioCiclo::atual();

        for ($i = 1; $i <= 30; $i++) {
            CursoExecucao::create([
                'curso' => 'Registro importado '.$i,
                'curso_id' => null,
                'eixo' => 'Gastronomia e Turismo',
                'codigo' => 'IMP-'.$i,
                'ciclo_id' => $ciclo?->id,
            ]);
        }

        $pagina1 = $this->getJson('/api/revisao-dados?tipo=sem_vinculo&per_page=25&page=1');
        $pagina1->assertOk();
        $this->assertCount(25, $pagina1->json('data'));
        $this->assertSame(30, $pagina1->json('meta.total'));
        $this->assertSame(30, $pagina1->json('totais.sem_vinculo'));
        $this->assertSame(1, $pagina1->json('meta.from'));
        $this->assertSame(25, $pagina1->json('meta.to'));
        $this->assertSame(2, $pagina1->json('meta.last_page'));
        $this->assertArrayNotHasKey('amostra', $pagina1->json());

        $pagina2 = $this->getJson('/api/revisao-dados?tipo=sem_vinculo&per_page=25&page=2');
        $this->assertCount(5, $pagina2->json('data'));
        $this->assertSame(26, $pagina2->json('meta.from'));
        $this->assertSame(30, $pagina2->json('meta.to'));
    }

    public function test_classificar_curso_sem_eixo_atualiza_card(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $ciclo = PortfolioCiclo::atual();
        $curso = Curso::create([
            'titulo' => 'Curso sem eixo',
            'status' => 'ATIVO',
            'ciclo_id' => $ciclo?->id,
        ]);
        $this->assertNull($curso->fresh()->eixo_id);

        $this->postJson('/api/revisao-dados/cursos/'.$curso->id.'/classificar', [
            'eixo' => 'Beleza e Cuidado Pessoal',
            'segmento' => 'Beleza e cuidado pessoal',
        ])->assertOk();

        $this->assertSame('Beleza e Cuidado Pessoal', $curso->fresh()->eixo);
        $card = collect($this->getJson('/api/eixos/resumo')->json('data.eixos'))
            ->firstWhere('nome', 'Beleza e Cuidado Pessoal');
        $this->assertSame(1, $card['cursos']);
    }

    public function test_vincular_registro_importado_atualiza_indicadores(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $ciclo = PortfolioCiclo::atual();
        $curso = Curso::create([
            'titulo' => 'Desenvolvimento de Websites',
            'status' => 'ATIVO',
            'eixo' => 'Tecnologia e Economia Criativa',
            'segmento' => 'Tecnologia da Informação - Desenvolvimento',
            'ciclo_id' => $ciclo?->id,
        ]);
        $pendencia = CursoExecucao::create([
            'curso' => 'Desenvolvimento Web',
            'curso_id' => null,
            'eixo' => 'Tecnologia e Economia Criativa',
            'codigo' => '2025.08.001',
            'turmas' => '2',
            'alunos' => '30',
            'ciclo_id' => $ciclo?->id,
        ]);

        $antes = collect($this->getJson('/api/eixos/resumo')->json('data.eixos'))
            ->firstWhere('nome', 'Tecnologia e Economia Criativa');
        $this->assertSame(0, $antes['turmas']);

        $this->postJson('/api/revisao-dados/execucoes/'.$pendencia->id.'/vincular', [
            'curso_id' => $curso->id,
        ])->assertOk();

        $depois = collect($this->getJson('/api/eixos/resumo')->json('data.eixos'))
            ->firstWhere('nome', 'Tecnologia e Economia Criativa');
        $this->assertSame(2, $depois['turmas']);
        $this->assertSame(30, $depois['alunos']);
    }

    public function test_nao_vincula_ciclos_diferentes(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $cicloAtual = PortfolioCiclo::atual();
        $outro = PortfolioCiclo::create(['nome' => '2018-2019', 'atual' => false]);
        $curso = Curso::create([
            'titulo' => 'Curso atual',
            'status' => 'ATIVO',
            'eixo' => 'Gestão e Moda',
            'ciclo_id' => $cicloAtual?->id,
        ]);
        $pendencia = CursoExecucao::create([
            'curso' => 'Curso atual',
            'curso_id' => null,
            'eixo' => 'Gestão e Moda',
            'ciclo_id' => $outro->id,
        ]);

        $this->postJson('/api/revisao-dados/execucoes/'.$pendencia->id.'/vincular', [
            'curso_id' => $curso->id,
        ])->assertStatus(422);
    }
}
