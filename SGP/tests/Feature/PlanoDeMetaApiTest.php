<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanoDeMetaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_and_create_plano_de_meta(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678900',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/plano-de-metas');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
        ]);

        $payload = [
            'segmento' => 'Infraestrutura',
            'curso' => 'Curso de Teste',
            'tipo' => 'QUALIFICAÇÃO',
            'numero_sei' => 'SEI-2026-001',
            'codigo_sig' => 'SIG-001',
            'mes_entrega' => 'Janeiro',
            'status' => 'EM ANÁLISE',
            'origem' => 'Plano de Metas',
            'status_final' => 'PUBLICADO',
            'observacao' => 'Registro de teste para validação da API.',
        ];

        $createResponse = $this->postJson('/api/plano-de-metas', $payload);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('planoDeMeta.curso', 'Curso de Teste');
    }
}
