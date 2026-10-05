<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Backups, cópias de importação e documentos PDF vão para um disco temporário:
        // os testes nunca gravam nem podam arquivos em storage/app/private.
        Storage::fake('local');
    }
}
