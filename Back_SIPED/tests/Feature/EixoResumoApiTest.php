<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\Eixo;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EixoResumoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Resumo Eixos',
            'email' => 'editor-resumo-eixos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678111',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991011',
        ]);
    }

    public function test_resumo_lista_os_cinco_eixos_oficiais(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->getJson('/api/eixos/resumo');
        $response->assertOk();
        $response->assertJsonPath('data.eixos.0.nome', 'Gastronomia e Turismo');
        $this->assertCount(5, $response->json('data.eixos'));
        $this->assertSame(0, $response->json('data.pendentes.ofertas'));
        $this->assertSame(
            [
                'Gastronomia e Turismo',
                'Ambiente e Saúde',
                'Gestão e Moda',
                'Tecnologia e Economia Criativa',
                'Beleza e Cuidado Pessoal',
            ],
            collect($response->json('data.eixos'))->pluck('nome')->all()
        );
    }

    public function test_resumo_e_detalhes_agregam_cursos_e_ofertas_do_ciclo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $eixoSaude = Eixo::query()->where('nome', 'Ambiente e Saúde')->firstOrFail();
        $cicloAtual = PortfolioCiclo::atual();
        $cicloAnterior = PortfolioCiclo::create(['nome' => '2022-2023', 'atual' => false]);

        $curso = Curso::create([
            'titulo' => 'Cuidador de Idosos',
            'status' => 'ATIVO',
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'ciclo_id' => $cicloAtual?->id,
        ]);

        Curso::create([
            'titulo' => 'Curso do ciclo anterior',
            'status' => 'ATIVO',
            'eixo' => 'Ambiente e Saúde',
            'ciclo_id' => $cicloAnterior->id,
        ]);

        CursoPorEixo::create([
            'curso' => 'Cuidador de Idosos',
            'curso_id' => $curso->id,
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'codigo' => 'EIX-1',
            'turmas' => '2',
            'alunos' => '40',
            'ano' => '2026',
            'status' => 'Ativo',
        ]);

        $resumo = $this->getJson('/api/eixos/resumo');
        $resumo->assertOk();
        $card = collect($resumo->json('data.eixos'))->firstWhere('nome', 'Ambiente e Saúde');
        $this->assertSame(1, $card['cursos']);
        $this->assertSame(1, $card['ofertas']);
        $this->assertSame(2, $card['turmas']);
        $this->assertSame(40, $card['alunos']);

        $detalhes = $this->getJson('/api/eixos/'.$eixoSaude->id.'/detalhes');
        $detalhes->assertOk();
        $detalhes->assertJsonPath('data.eixo.nome', 'Ambiente e Saúde');
        $detalhes->assertJsonPath('data.ofertas.0.curso', 'Cuidador de Idosos');
        $enfermagem = collect($detalhes->json('data.segmentos'))->firstWhere('nome', 'Enfermagem');
        $this->assertSame(1, $enfermagem['cursos']);
        $this->assertSame(1, $enfermagem['ofertas']);
    }
}
