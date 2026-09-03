<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    public function test_endpoint_up_retorna_200_sem_dados_sensiveis(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $corpo = $response->getContent();

        $this->assertStringNotContainsStringIgnoringCase('DB_PASSWORD', $corpo);
        $this->assertStringNotContainsStringIgnoringCase('APP_KEY', $corpo);
        $this->assertStringNotContainsStringIgnoringCase('.env', $corpo);
        $this->assertStringNotContainsStringIgnoringCase('mysql', $corpo);
    }

    public function test_comando_check_disk_com_thresholds_artificiais_altos_retorna_critico(): void
    {
        // Thresholds absurdoamente altos: qualquer disco real fica abaixo → exit 2.
        $codigo = Artisan::call('sgp:check-disk', [
            '--warn-percent' => 99.9,
            '--warn-gb' => 99999,
            '--crit-percent' => 99.9,
            '--crit-gb' => 99999,
            '--json' => true,
        ]);

        $this->assertSame(2, $codigo);
        $saida = Artisan::output();
        $this->assertStringContainsString('"status": "critical"', $saida);
    }

    public function test_comando_check_disk_com_thresholds_zero_retorna_ok(): void
    {
        // Thresholds 0: livre > 0% e > 0 GB → OK (exit 0) em disco utilizável.
        $codigo = Artisan::call('sgp:check-disk', [
            '--warn-percent' => 0,
            '--warn-gb' => 0,
            '--crit-percent' => 0,
            '--crit-gb' => 0,
            '--json' => true,
        ]);

        $this->assertSame(0, $codigo);
        $saida = Artisan::output();
        $this->assertStringContainsString('"status": "ok"', $saida);
    }
}
