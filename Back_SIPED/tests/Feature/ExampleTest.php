<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_root_redirects_to_the_frontend_login(): void
    {
        config([
            'app.frontend_url' => 'http://127.0.0.1:5173',
            'app.debug' => true,
        ]);

        $this->get('/')->assertRedirect('http://127.0.0.1:5173/login');
    }

    public function test_deep_frontend_route_redirects_to_vite_when_debug_is_enabled(): void
    {
        config([
            'app.frontend_url' => 'http://127.0.0.1:5173',
            'app.debug' => true,
        ]);

        $this->get('/app/ferramentas/kanban')->assertRedirect('http://127.0.0.1:5173/app/ferramentas/kanban');
    }
}
