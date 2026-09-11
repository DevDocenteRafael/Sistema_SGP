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
        $this->assertSame(5, $response->json('data.totais.eixos'));
        $this->assertNull($response->json('data.pendentes'));
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
        $this->assertArrayNotHasKey('ofertas', $card);
        $this->assertSame(2, $card['turmas']);
        $this->assertSame(40, $card['alunos']);

        $detalhes = $this->getJson('/api/eixos/'.$eixoSaude->id.'/detalhes');
        $detalhes->assertOk();
        $detalhes->assertJsonPath('data.eixo.nome', 'Ambiente e Saúde');
        $this->assertArrayNotHasKey('ofertas', $detalhes->json('data'));
        $enfermagem = collect($detalhes->json('data.segmentos'))->firstWhere('nome', 'Enfermagem');
        $this->assertSame(1, $enfermagem['cursos']);
        $this->assertSame(2, $enfermagem['turmas']);

        $cursos = $this->getJson('/api/eixos/'.$eixoSaude->id.'/cursos?per_page=25');
        $cursos->assertOk();
        $cursos->assertJsonPath('data.0.titulo', 'Cuidador de Idosos');
        $cursos->assertJsonPath('meta.total', 1);
    }

    public function test_duas_ofertas_do_mesmo_curso_contam_um_curso_no_card(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAtual = PortfolioCiclo::atual();
        $curso = Curso::create([
            'titulo' => 'Cuidador de Idosos',
            'status' => 'ATIVO',
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'ciclo_id' => $cicloAtual?->id,
        ]);

        CursoPorEixo::create([
            'curso' => 'Cuidador de Idosos',
            'curso_id' => $curso->id,
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'codigo' => 'EIX-A',
            'turmas' => '1',
            'alunos' => '20',
            'ciclo_id' => $cicloAtual?->id,
        ]);
        CursoPorEixo::create([
            'curso' => 'Cuidador de Idosos',
            'curso_id' => $curso->id,
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'codigo' => 'EIX-B',
            'turmas' => '1',
            'alunos' => '15',
            'ciclo_id' => $cicloAtual?->id,
        ]);

        $resumo = $this->getJson('/api/eixos/resumo');
        $resumo->assertOk();
        $card = collect($resumo->json('data.eixos'))->firstWhere('nome', 'Ambiente e Saúde');
        $this->assertSame(1, $card['cursos']);
        $this->assertSame(2, $card['turmas']);
        $this->assertSame(35, $card['alunos']);
    }

    public function test_registro_sem_curso_id_nao_entra_em_turmas_nem_alunos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        CursoPorEixo::create([
            'curso' => 'Açougueiro',
            'curso_id' => null,
            'eixo' => 'Gastronomia e Turismo',
            'segmento' => 'Gastronomia',
            'codigo' => 'EIX-PEND',
            'turmas' => '8',
            'alunos' => '80',
        ]);

        $resumo = $this->getJson('/api/eixos/resumo');
        $resumo->assertOk();
        $card = collect($resumo->json('data.eixos'))->firstWhere('nome', 'Gastronomia e Turismo');
        $this->assertSame(0, $card['cursos']);
        $this->assertSame(0, $card['turmas']);
        $this->assertSame(0, $card['alunos']);
        $this->assertSame(0, $resumo->json('data.totais.turmas'));
    }

    public function test_cursos_do_eixo_sao_paginados(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $eixo = Eixo::query()->where('nome', 'Gestão e Moda')->firstOrFail();
        $ciclo = PortfolioCiclo::atual();
        for ($i = 1; $i <= 26; $i++) {
            Curso::create([
                'titulo' => sprintf('Curso paginado %02d', $i),
                'status' => 'ATIVO',
                'eixo' => 'Gestão e Moda',
                'segmento' => 'Gestão e Comércio',
                'ciclo_id' => $ciclo?->id,
            ]);
        }

        $pagina1 = $this->getJson('/api/eixos/'.$eixo->id.'/cursos?per_page=25&page=1');
        $pagina1->assertOk();
        $this->assertCount(25, $pagina1->json('data'));
        $this->assertSame(26, $pagina1->json('meta.total'));
        $this->assertSame(1, $pagina1->json('meta.from'));
        $this->assertSame(25, $pagina1->json('meta.to'));
        $this->assertSame(2, $pagina1->json('meta.last_page'));

        $pagina2 = $this->getJson('/api/eixos/'.$eixo->id.'/cursos?per_page=25&page=2');
        $this->assertCount(1, $pagina2->json('data'));
        $this->assertSame(26, $pagina2->json('meta.from'));
        $this->assertSame(26, $pagina2->json('meta.to'));
    }

    public function test_curso_novo_aparece_no_eixo_sem_criar_turmas_nem_alunos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAtual = PortfolioCiclo::atual();
        Curso::create([
            'titulo' => 'Desenvolvimento Web',
            'status' => 'ATIVO',
            'eixo' => 'Tecnologia e Economia Criativa',
            'segmento' => 'Tecnologia da Informação - Desenvolvimento',
            'ciclo_id' => $cicloAtual?->id,
            'turmas' => '9',
            'alunos' => '99',
        ]);

        $resumo = $this->getJson('/api/eixos/resumo');
        $resumo->assertOk();
        $card = collect($resumo->json('data.eixos'))->firstWhere('nome', 'Tecnologia e Economia Criativa');
        $this->assertSame(1, $card['cursos']);
        $this->assertSame(0, $card['turmas']);
        $this->assertSame(0, $card['alunos']);
        $this->assertSame(0, CursoPorEixo::query()->count());
        $this->assertSame(1, $resumo->json('data.totais.cursos'));
        $this->assertSame(5, $resumo->json('data.totais.eixos'));
    }

    public function test_acompanhamento_manual_atualiza_turmas_e_alunos_do_card(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAtual = PortfolioCiclo::atual();
        $curso = Curso::create([
            'titulo' => 'Cuidador de Idosos',
            'status' => 'ATIVO',
            'eixo' => 'Ambiente e Saúde',
            'segmento' => 'Enfermagem',
            'ciclo_id' => $cicloAtual?->id,
            'turmas' => '80',
            'alunos' => '800',
        ]);

        $this->postJson('/api/curso-execucoes', [
            'curso_id' => $curso->id,
            'turmas' => '3',
            'alunos' => '45',
            'status' => 'Em andamento',
        ])->assertCreated();

        $resumo = $this->getJson('/api/eixos/resumo');
        $card = collect($resumo->json('data.eixos'))->firstWhere('nome', 'Ambiente e Saúde');
        $this->assertSame(1, $card['cursos']);
        $this->assertSame(3, $card['turmas']);
        $this->assertSame(45, $card['alunos']);
        $this->assertNotEquals(80, $card['turmas']);
    }

    public function test_soma_dos_cursos_dos_cinco_eixos_bate_com_o_catalogo_do_ciclo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAtual = PortfolioCiclo::atual();
        foreach ([
            'Gastronomia e Turismo' => 'Gastronomia',
            'Ambiente e Saúde' => 'Enfermagem',
            'Tecnologia e Economia Criativa' => 'Tecnologia da Informação - Desenvolvimento',
        ] as $eixo => $segmento) {
            Curso::create([
                'titulo' => 'Curso '.$eixo,
                'status' => 'ATIVO',
                'eixo' => $eixo,
                'segmento' => $segmento,
                'ciclo_id' => $cicloAtual?->id,
            ]);
        }

        $resumo = $this->getJson('/api/eixos/resumo');
        $resumo->assertOk();
        $somaCards = collect($resumo->json('data.eixos'))->sum('cursos');
        $this->assertSame(3, $somaCards);
        $this->assertSame(3, $resumo->json('data.totais.cursos_classificados'));
        $this->assertSame(3, $this->getJson('/api/cursos')->json('meta.total'));
    }

    public function test_troca_de_ciclo_isola_indicadores(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAtual = PortfolioCiclo::atual();
        $cicloAnterior = PortfolioCiclo::create(['nome' => '2020-2021', 'atual' => false]);

        Curso::create([
            'titulo' => 'Curso atual',
            'status' => 'ATIVO',
            'eixo' => 'Beleza e Cuidado Pessoal',
            'ciclo_id' => $cicloAtual?->id,
        ]);
        Curso::create([
            'titulo' => 'Segundo curso atual',
            'status' => 'ATIVO',
            'eixo' => 'Beleza e Cuidado Pessoal',
            'ciclo_id' => $cicloAtual?->id,
        ]);
        Curso::create([
            'titulo' => 'Curso antigo',
            'status' => 'ATIVO',
            'eixo' => 'Beleza e Cuidado Pessoal',
            'ciclo_id' => $cicloAnterior->id,
        ]);

        $atual = $this->getJson('/api/eixos/resumo?ciclo_id='.$cicloAtual->id);
        $cardAtual = collect($atual->json('data.eixos'))->firstWhere('nome', 'Beleza e Cuidado Pessoal');
        $this->assertSame(2, $cardAtual['cursos']);
        $this->assertSame(2, $atual->json('data.totais.cursos'));

        $anterior = $this->getJson('/api/eixos/resumo?ciclo_id='.$cicloAnterior->id);
        $cardAnterior = collect($anterior->json('data.eixos'))->firstWhere('nome', 'Beleza e Cuidado Pessoal');
        $this->assertSame(1, $cardAnterior['cursos']);
        $this->assertSame(1, $anterior->json('data.totais.cursos'));
        $this->assertNotEquals($atual->json('data.totais.cursos'), $anterior->json('data.totais.cursos'));
    }
}
