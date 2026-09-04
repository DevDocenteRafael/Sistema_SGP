<?php

namespace Tests\Feature;

use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
use App\Models\Usuario;
use Database\Seeders\UnidadeOfertaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnidadeOfertaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Unidades',
            'email' => 'editor-unidades@teste.com',
            'senha' => Hash::make('senha123'),
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
        ]);
    }

    public function test_lista_nomes_e_opcoes_apos_seed(): void
    {
        $this->seed(UnidadeOfertaSeeder::class);
        $this->actingAs($this->editor(), 'sanctum');

        $nomes = $this->getJson('/api/unidades-oferta/nomes');
        $nomes->assertOk();
        $this->assertContains('Taguatinga', $nomes->json('data'));

        $opcoes = $this->getJson('/api/unidades-oferta/opcoes');
        $opcoes->assertOk();
        $this->assertNotEmpty($opcoes->json('data'));
        $this->assertArrayHasKey('grupos', $opcoes->json('data.0'));
    }

    public function test_cria_e_inativa_unidade_sem_excluir(): void
    {
        $this->seed(UnidadeOfertaSeeder::class);
        $this->actingAs($this->editor(), 'sanctum');

        $regiao = RegiaoAdministrativa::query()->where('nome', 'Asa Norte')->firstOrFail();

        $create = $this->postJson('/api/unidades-oferta', [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Polo Asa Norte Teste',
            'tipo' => UnidadeOferta::TIPO_POLO,
            'ativo' => true,
        ]);
        $create->assertCreated();
        $id = $create->json('unidade_oferta.id');

        $this->putJson("/api/unidades-oferta/{$id}", [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Polo Asa Norte Teste',
            'tipo' => UnidadeOferta::TIPO_POLO,
            'ativo' => false,
        ])->assertOk()->assertJsonPath('unidade_oferta.ativo', false);

        $this->assertDatabaseHas('unidades_oferta', [
            'id' => $id,
            'ativo' => false,
        ]);

        $this->deleteJson("/api/unidades-oferta/{$id}")->assertStatus(405);
    }

    public function test_cria_e_inativa_regiao(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $create = $this->postJson('/api/regioes-administrativas', [
            'nome' => 'RA Teste',
            'ativo' => true,
        ]);
        $create->assertCreated();
        $id = $create->json('regiao_administrativa.id');

        $this->putJson("/api/regioes-administrativas/{$id}", [
            'nome' => 'RA Teste',
            'ativo' => false,
        ])->assertOk()->assertJsonPath('regiao_administrativa.ativo', false);

        $this->deleteJson("/api/regioes-administrativas/{$id}")->assertStatus(405);
    }
}
