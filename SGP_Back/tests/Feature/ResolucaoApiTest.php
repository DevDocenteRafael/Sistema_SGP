<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResolucaoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_create_show_update_and_delete_resolucao_with_auto_vigencia(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-resolucao@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678901',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $payload = [
            'numero' => 'MEC/2026/001',
            'curso_relacionado' => 'Técnico em Administração',
            'categoria' => 'Normativa',
            'resumo' => 'Aprovação de curso técnico em administração',
            'relator' => 'Maria Souza',
            'setor' => 'CPED',
            'data_inicio_vigencia' => '2026-08-12',
            'status' => 'vigente',
            'observacoes' => 'Resolução inicial para acompanhamento.',
        ];

        $createResponse = $this->postJson('/api/resolucoes', $payload);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('resolucao.numero', 'MEC/2026/001');
        $createResponse->assertJsonPath('resolucao.data_fim_vigencia', '2031-08-12');

        $id = $createResponse->json('resolucao.id');

        $listResponse = $this->getJson('/api/resolucoes');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'vigencia_anos',
                'status',
                'semaforo',
            ],
        ]);

        $showResponse = $this->getJson('/api/resolucoes/' . $id);
        $showResponse->assertOk();
        $showResponse->assertJsonPath('resolucao.id', $id);
        $showResponse->assertJsonPath('resolucao.data_fim_vigencia', '2031-08-12');

        $updatePayload = [
            ...$payload,
            'data_inicio_vigencia' => '2021-01-10',
            'status' => 'atencao',
            'observacoes' => 'Atualização de vigência.',
        ];

        $updateResponse = $this->putJson('/api/resolucoes/' . $id, $updatePayload);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('resolucao.data_fim_vigencia', '2026-01-10');

        $this->assertDatabaseHas('resolucao_historicos', [
            'resolucao_id' => $id,
            'evento' => 'Resolução cadastrada',
        ]);
        $this->assertDatabaseHas('resolucao_historicos', [
            'resolucao_id' => $id,
            'evento' => 'Status alterado',
            'status_anterior' => 'vigente',
            'status_novo' => 'atencao',
            'usuario_id' => $usuario->id,
        ]);

        $showAfterUpdate = $this->getJson('/api/resolucoes/' . $id);
        $showAfterUpdate->assertOk();
        $showAfterUpdate->assertJsonPath('resolucao.historicos.0.acao', 'Vigência alterada');

        $deleteResponse = $this->deleteJson('/api/resolucoes/' . $id);
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('resolucoes', ['id' => $id]);
    }

    public function test_filters_resolucoes_by_status_and_setor(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor Filtro',
            'email' => 'editor-filtro-resolucao@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678902',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11988888888',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $this->postJson('/api/resolucoes', [
            'numero' => 'MEC/2026/010',
            'curso_relacionado' => 'Técnico em Logística',
            'categoria' => 'Normativa',
            'resumo' => 'Primeira resolução',
            'relator' => 'Lucas Silva',
            'setor' => 'CPED',
            'data_inicio_vigencia' => '2026-01-01',
            'status' => 'vigente',
        ]);

        $this->postJson('/api/resolucoes', [
            'numero' => 'MEC/2026/020',
            'curso_relacionado' => 'Técnico em Eletrônica',
            'categoria' => 'Operacional',
            'resumo' => 'Segunda resolução',
            'relator' => 'Patrícia Lima',
            'setor' => 'Gabinete',
            'data_inicio_vigencia' => '2025-06-01',
            'status' => 'atencao',
        ]);

        $filtered = $this->getJson('/api/resolucoes?status=vigente&setor=CPED');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.numero', 'MEC/2026/010');
    }
}
