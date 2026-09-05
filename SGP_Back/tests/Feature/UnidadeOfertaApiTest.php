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
            'nome' => 'Editor Estruturas',
            'email' => 'editor-estruturas@teste.com',
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
        $this->assertContains('Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga', $nomes->json('data'));
        $this->assertContains('Senac Brazlândia', $nomes->json('data'));
        $this->assertContains('Centro de Educação Profissional Joaquim Loiola — Gama', $nomes->json('data'));

        $opcoes = $this->getJson('/api/unidades-oferta/opcoes');
        $opcoes->assertOk();
        $this->assertNotEmpty($opcoes->json('data'));
        $this->assertArrayHasKey('grupos', $opcoes->json('data.0'));
        $this->assertSame('Faculdade', $opcoes->json('meta.tipos.faculdade'));
        $this->assertSame('Unidade', $opcoes->json('meta.tipos.unidade'));
    }

    public function test_cria_faculdade_polo_e_unidade_com_metadados(): void
    {
        $this->seed(UnidadeOfertaSeeder::class);
        $this->actingAs($this->editor(), 'sanctum');

        $regiao = RegiaoAdministrativa::query()->where('nome', 'Asa Norte')->firstOrFail();

        $faculdade = $this->postJson('/api/unidades-oferta', [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Faculdade Teste Asa Norte',
            'tipo' => UnidadeOferta::TIPO_FACULDADE,
            'codigo' => '999',
            'endereco' => 'Asa Norte QI 00',
            'responsavel' => 'Responsável Teste',
            'ativo' => true,
        ]);
        $faculdade->assertCreated()
            ->assertJsonPath('unidade_oferta.tipo', 'faculdade')
            ->assertJsonPath('unidade_oferta.codigo', '999');

        $polo = $this->postJson('/api/unidades-oferta', [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Polo Teste Asa Norte',
            'tipo' => UnidadeOferta::TIPO_POLO,
            'ativo' => true,
        ]);
        $polo->assertCreated()->assertJsonPath('unidade_oferta.tipo', 'polo');

        $unidade = $this->postJson('/api/unidades-oferta', [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Unidade Teste Asa Norte',
            'tipo' => UnidadeOferta::TIPO_UNIDADE,
            'ativo' => true,
        ]);
        $unidade->assertCreated()->assertJsonPath('unidade_oferta.tipo', 'unidade');
    }

    public function test_cria_estrutura_com_localidade_em_texto(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->postJson('/api/unidades-oferta', [
            'localidade' => 'Riacho Fundo II',
            'nome' => 'Polo Teste Riacho Fundo II',
            'tipo' => UnidadeOferta::TIPO_POLO,
            'ativo' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('unidade_oferta.tipo', 'polo')
            ->assertJsonPath('unidade_oferta.regiao_administrativa.nome', 'Riacho Fundo II');

        $this->assertDatabaseHas('regioes_administrativas', ['nome' => 'Riacho Fundo II']);
    }

    public function test_cria_e_inativa_estrutura_sem_excluir(): void
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
            'motivo_inativacao' => 'Encerramento temporário da oferta.',
        ])->assertOk()
            ->assertJsonPath('unidade_oferta.ativo', false)
            ->assertJsonPath('unidade_oferta.motivo_inativacao', 'Encerramento temporário da oferta.');

        $this->assertDatabaseHas('unidades_oferta', [
            'id' => $id,
            'ativo' => false,
            'motivo_inativacao' => 'Encerramento temporário da oferta.',
        ]);

        $this->putJson("/api/unidades-oferta/{$id}", [
            'regiao_administrativa_id' => $regiao->id,
            'nome' => 'Polo Asa Norte Teste',
            'tipo' => UnidadeOferta::TIPO_POLO,
            'ativo' => true,
        ])->assertOk()
            ->assertJsonPath('unidade_oferta.ativo', true)
            ->assertJsonPath('unidade_oferta.motivo_inativacao', null);

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
