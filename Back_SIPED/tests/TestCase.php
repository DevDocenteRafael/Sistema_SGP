<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function assertEscritaExternaBloqueada(TestResponse $response): void
    {
        $response->assertForbidden()
            ->assertJsonPath('message', config('origem_dados.mensagem_bloqueio'));
    }
}
