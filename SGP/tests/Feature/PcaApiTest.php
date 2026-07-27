<?php

namespace Tests\Feature;

use App\Models\Pca;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PcaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_and_create_pca(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-pca@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678901',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/pcas');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
            'meta' => ['total', 'total_geral', 'status', 'anos', 'semestres', 'eixos', 'unidades'],
        ]);

        $payload = [
            'titulo' => 'Técnico em Administração',
            'semestre' => '2026/1',
            'numero_sei' => 'SEI-2026-007',
            'codigo_sig' => 'SIG-PCA-01',
            'eixo' => 'Gestão e Moda',
            'unidade' => 'Taguatinga',
            'carga_horaria' => '1200',
            'status' => 'Vigente',
            'ano' => 2026,
            'observacao' => 'Registro de teste para validação da API PCA.',
        ];

        $createResponse = $this->postJson('/api/pcas', $payload);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('pca.titulo', 'Técnico em Administração');
    }

    public function test_can_create_pca_with_pricing_fields(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor 2',
            'email' => 'editor-pca-2@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678902',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999998',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $payload = [
            'titulo' => 'Curso com Precificação',
            'semestre' => '2026/2',
            'numero_sei' => 'SEI-2026-008',
            'codigo_sig' => 'SIG-PCA-02',
            'eixo' => 'Ambiente e Saúde',
            'unidade' => 'Sobradinho',
            'carga_horaria' => '800',
            'precificacao' => 'R$ 3.000,00',
            'valor_primeiro_modulo' => 'R$ 500,00',
            'valor' => 'R$ 3.000,00',
            'parcelas_boleto' => '6',
            'valor_parcela_boleto' => 'R$ 500,00',
            'parcelas_cartao' => '6',
            'valor_cartao' => 'R$ 500,00',
            'parcela_desc_20' => 'R$ 400,00',
            'parcela_desc_15' => 'R$ 425,00',
            'status' => 'Em análise',
            'ano' => 2026,
        ];

        $response = $this->postJson('/api/pcas', $payload);

        $response->assertCreated();
        $response->assertJsonPath('pca.precificacao', 'R$ 3.000,00');
        $response->assertJsonPath('pca.parcela_desc_20', 'R$ 400,00');
    }

    public function test_can_filter_pca_by_status_and_eixo(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Consultor',
            'email' => 'consultor-pca@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678903',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999997',
        ]);

        Pca::create([
            'titulo' => 'Curso Vigente',
            'semestre' => '2026/1',
            'numero_sei' => 'SEI-FILTRO-01',
            'codigo_sig' => 'SIG-FILTRO-01',
            'eixo' => 'Gastronomia',
            'unidade' => 'Taguatinga',
            'status' => 'Vigente',
            'ano' => 2026,
        ]);

        Pca::create([
            'titulo' => 'Curso Suspenso',
            'semestre' => '2026/2',
            'numero_sei' => 'SEI-FILTRO-02',
            'codigo_sig' => 'SIG-FILTRO-02',
            'eixo' => 'Ambiente e Saúde',
            'unidade' => 'Sobradinho',
            'status' => 'Suspenso',
            'ano' => 2026,
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->getJson('/api/pcas?status=Vigente&eixo=Gastronomia');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.titulo', 'Curso Vigente');
    }

    public function test_show_requires_consult_permission(): void
    {
        $pca = Pca::create([
            'titulo' => 'Curso Restrito',
            'semestre' => '2026/1',
            'numero_sei' => 'SEI-SHOW-01',
            'codigo_sig' => 'SIG-SHOW-01',
            'status' => 'Vigente',
            'ano' => 2026,
        ]);

        $this->getJson("/api/pcas/{$pca->id}")
            ->assertUnauthorized();
    }
}
