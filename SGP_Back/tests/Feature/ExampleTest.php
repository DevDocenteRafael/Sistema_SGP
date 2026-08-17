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
        config(['app.frontend_url' => 'http://127.0.0.1:5173']);

        $this->get('/')->assertRedirect('http://127.0.0.1:5173/login');
    }
}
