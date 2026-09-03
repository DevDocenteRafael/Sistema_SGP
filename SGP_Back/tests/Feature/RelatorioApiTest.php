<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Resolucao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RelatorioApiTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(string $perfil = Usuario::PERFIL_EDITOR): Usuario
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Relatorios',
            'email' => 'relatorios-'.uniqid().'@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => (string) random_int(10000000000, 99999999999),
            'perfil' => $perfil,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '61999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    public function test_pode_listar_catalogo_de_relatorios(): void
    {
        $this->autenticar();

        Curso::create([
            'titulo' => 'Curso Relatório',
            'eixo' => 'Gestão e Moda',
            'status' => 'ATIVO',
            'unidade' => 'Taguatinga',
        ]);

        $response = $this->getJson('/api/relatorios');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                ['key', 'label', 'description', 'api', 'filtros', 'colunas', 'total'],
            ],
            'meta',
        ]);

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertContains('cursos', $keys);
        $this->assertContains('pcas', $keys);
        $this->assertContains('jornadas-pedagogicas', $keys);
        $this->assertContains('eventos', $keys);

        $cursos = collect($response->json('data'))->firstWhere('key', 'cursos');
        $this->assertSame(1, $cursos['total']);
    }

    public function test_preview_de_cursos_e_eixos_retorna_todos_os_registros(): void
    {
        $this->autenticar();

        for ($i = 1; $i <= 3; $i++) {
            Curso::create([
                'titulo' => "Curso Preview {$i}",
                'eixo' => 'Gestão e Moda',
                'status' => 'ATIVO',
            ]);
        }

        \App\Models\CursoPorEixo::create([
            'curso' => 'Curso Eixo A',
            'eixo' => 'Gastronomia',
            'ch' => '40',
        ]);
        \App\Models\CursoPorEixo::create([
            'curso' => 'Curso Eixo B',
            'eixo' => 'Bebidas',
            'ch' => '80',
        ]);

        $cursos = $this->getJson('/api/relatorios/cursos/preview');
        $cursos->assertOk();
        $this->assertSame(3, $cursos->json('meta.total'));
        $this->assertSame(3, $cursos->json('meta.total_exibido'));
        $this->assertFalse($cursos->json('meta.truncado'));
        $this->assertCount(3, $cursos->json('data'));

        $eixos = $this->getJson('/api/relatorios/eixos/preview');
        $eixos->assertOk();
        $this->assertSame(2, $eixos->json('meta.total'));
        $this->assertCount(2, $eixos->json('data'));
        $this->assertContains('Bebidas', $eixos->json('meta.eixos'));
    }

    public function test_pode_listar_relatorio_de_resolucoes_no_catalogo_e_exportar_pdf(): void
    {
        $this->autenticar();

        Resolucao::create([
            'numero' => 'RES-2026/001',
            'curso_relacionado' => 'Técnico em Administração',
            'categoria' => 'Normativa',
            'resumo' => 'Aprovação de normas internas',
            'relator' => 'Maria Souza',
            'setor' => 'CPED',
            'data_inicio_vigencia' => '2026-01-01',
            'data_fim_vigencia' => '2031-01-01',
            'status' => 'vigente',
        ]);

        $catalogo = $this->getJson('/api/relatorios');
        $catalogo->assertOk();
        $this->assertContains('resolucoes', collect($catalogo->json('data'))->pluck('key')->all());
        $this->assertContains('termos-referencia', collect($catalogo->json('data'))->pluck('key')->all());

        $resolucao = collect($catalogo->json('data'))->firstWhere('key', 'resolucoes');
        $this->assertSame(1, $resolucao['total']);

        $pdf = $this->get('/api/relatorios/resolucoes/pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
    }

    public function test_pode_exportar_pdf_de_cursos(): void
    {
        $this->autenticar();

        Curso::create([
            'titulo' => 'Técnico em Administração',
            'eixo' => 'Gestão e Moda',
            'tipo' => 'Técnico',
            'status' => 'ATIVO',
            'unidade' => 'Taguatinga',
            'codigo_sig' => 'SIG-001',
            'ultima_revisao' => '2026',
        ]);

        $response = $this->get('/api/relatorios/cursos/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertGreaterThan(100, strlen($response->getContent()));
    }

    public function test_pode_exportar_pdf_dos_demais_tipos(): void
    {
        $this->autenticar();

        $tipos = [
            'plano-de-metas',
            'pcas',
            'eixos',
            'jornadas-pedagogicas',
            'visitas-tecnicas',
            'horas-pedagogicas',
            'acoes-extensivas',
            'eventos',
            'termos-referencia',
        ];

        foreach ($tipos as $tipo) {
            $response = $this->get("/api/relatorios/{$tipo}/pdf");
            $response->assertOk("Falha ao exportar PDF do tipo {$tipo}");
            $this->assertStringContainsString(
                'application/pdf',
                (string) $response->headers->get('content-type'),
                "Content-Type inválido para {$tipo}"
            );
        }
    }

    public function test_tipo_invalido_retorna_404(): void
    {
        $this->autenticar();

        $response = $this->getJson('/api/relatorios/inexistente/pdf');

        $response->assertNotFound();
        $response->assertJsonPath('message', 'Tipo de relatório não encontrado.');
    }

    public function test_nao_autenticado_retorna_401(): void
    {
        $this->getJson('/api/relatorios')->assertUnauthorized();
        $this->getJson('/api/relatorios/cursos/pdf')->assertUnauthorized();
    }
}
