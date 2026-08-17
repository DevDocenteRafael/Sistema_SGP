<?php

namespace Tests\Feature;

use App\Models\PlanoDeMeta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanoDeMetaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Metas',
            'email' => 'editor-metas@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678102',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991002',
        ]);
    }

    public function test_can_list_create_show_update_and_delete_plano_de_meta(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/plano-de-metas')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'total_geral', 'anos', 'segmentos', 'tipos', 'meses', 'status', 'situacoes'],
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
            'ano' => 2026,
        ];

        $create = $this->postJson('/api/plano-de-metas', $payload);
        $create->assertCreated();
        $create->assertJsonPath('planoDeMeta.curso', 'Curso de Teste');

        $id = $create->json('planoDeMeta.id');

        $this->getJson("/api/plano-de-metas/{$id}")
            ->assertOk()
            ->assertJsonPath('planoDeMeta.codigo_sig', 'SIG-001');

        $this->putJson("/api/plano-de-metas/{$id}", [
            ...$payload,
            'curso' => 'Curso Atualizado',
            'status' => 'APROVADO',
            'status_final' => 'CONCLUÍDO',
        ])
            ->assertOk()
            ->assertJsonPath('planoDeMeta.curso', 'Curso Atualizado')
            ->assertJsonPath('planoDeMeta.status', 'APROVADO');

        $this->deleteJson("/api/plano-de-metas/{$id}")->assertOk();
        $this->assertDatabaseMissing('plano_de_metas', ['id' => $id]);
    }

    public function test_filters_plano_de_meta_by_status_and_segmento(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        PlanoDeMeta::create([
            'segmento' => 'Infraestrutura',
            'curso' => 'Meta A',
            'tipo' => 'QUALIFICAÇÃO',
            'numero_sei' => 'SEI-F-01',
            'codigo_sig' => 'SIG-F-01',
            'mes_entrega' => 'Janeiro',
            'status' => 'EM ANÁLISE',
            'status_final' => 'PUBLICADO',
            'ano' => 2026,
        ]);
        PlanoDeMeta::create([
            'segmento' => 'Pedagógico',
            'curso' => 'Meta B',
            'tipo' => 'LIVRE',
            'numero_sei' => 'SEI-F-02',
            'codigo_sig' => 'SIG-F-02',
            'mes_entrega' => 'Fevereiro',
            'status' => 'APROVADO',
            'status_final' => 'CONCLUÍDO',
            'ano' => 2026,
        ]);

        $filtered = $this->getJson('/api/plano-de-metas?status=EM ANÁLISE&segmento=Infraestrutura');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.curso', 'Meta A');
    }
}
