<?php

namespace Tests\Feature;

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
        $listResponse->assertJsonStructure(['data']);

        $payload = [
            'unidade' => 'DF',
            'curso' => 'Plano de Ação Teste',
            'tipo' => 'Presencial',
            'periodo' => '1º Semestre',
            'numero_sei' => 'SEI-2026-007',
            'codigo_sig' => 'SIG-PCA-01',
            'status' => 'Em andamento',
            'observacao' => 'Registro de teste para validação da API PCA.',
        ];

        $createResponse = $this->postJson('/api/pcas', $payload);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('pca.curso', 'Plano de Ação Teste');
    }

    public function test_can_create_pca_with_additional_fields(): void
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
            'unidade' => 'DF',
            'curso' => 'Plano de Ação Completo',
            'tipo' => 'Presencial',
            'periodo' => '2º Semestre',
            'numero_sei' => 'SEI-2026-008',
            'codigo_sig' => 'SIG-PCA-02',
            'status' => 'Planejado',
            'responsavel' => 'Ana Paula',
            'objetivo' => 'Aumentar a adesão ao plano pedagógico.',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-12-15',
            'observacao' => 'Campos adicionais do formulário.',
        ];

        $response = $this->postJson('/api/pcas', $payload);

        $response->assertCreated();
        $response->assertJsonPath('pca.responsavel', 'Ana Paula');
        $response->assertJsonPath('pca.objetivo', 'Aumentar a adesão ao plano pedagógico.');
    }
}
