<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FerramentaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_ferramentas_from_catalog(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-ferramentas@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678921',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->getJson('/api/ferramentas');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'key',
                    'label',
                    'description',
                    'type',
                    'route',
                    'url',
                    'enabled',
                    'status',
                    'icon',
                ],
            ],
            'meta' => ['total'],
        ]);

        $keys = collect($response->json('data'))->pluck('key')->all();

        $this->assertContains('kanban', $keys);
        $this->assertContains('organograma', $keys);
        $this->assertContains('carometro', $keys);
        $this->assertContains('fluxograma', $keys);
        $this->assertContains('microsoft_loop', $keys);
        $this->assertContains('canva', $keys);

        $kanban = collect($response->json('data'))->firstWhere('key', 'kanban');
        $this->assertSame('available', $kanban['status']);
        $this->assertTrue($kanban['enabled']);
        $this->assertSame('internal', $kanban['type']);
        $this->assertSame('/app/ferramentas/kanban', $kanban['route']);

        $organograma = collect($response->json('data'))->firstWhere('key', 'organograma');
        $this->assertSame('available', $organograma['status']);
        $this->assertTrue($organograma['enabled']);
        $this->assertSame('/app/ferramentas/organograma', $organograma['route']);

        $carometro = collect($response->json('data'))->firstWhere('key', 'carometro');
        $this->assertSame('available', $carometro['status']);
        $this->assertTrue($carometro['enabled']);
        $this->assertSame('/app/ferramentas/carometro', $carometro['route']);

        $loop = collect($response->json('data'))->firstWhere('key', 'microsoft_loop');
        $this->assertSame('available', $loop['status']);
        $this->assertTrue($loop['enabled']);
        $this->assertSame('external', $loop['type']);
    }

    public function test_unauthenticated_cannot_list_ferramentas(): void
    {
        $this->getJson('/api/ferramentas')->assertUnauthorized();
    }
}
