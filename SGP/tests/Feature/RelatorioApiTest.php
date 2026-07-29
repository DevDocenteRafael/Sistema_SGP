<?php

namespace Tests\Feature;

use App\Models\Curso;
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
        $this->assertContains('eventos', $keys);

        $cursos = collect($response->json('data'))->firstWhere('key', 'cursos');
        $this->assertSame(1, $cursos['total']);
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
            'visitas-tecnicas',
            'horas-pedagogicas',
            'acoes-extensivas',
            'eventos',
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
