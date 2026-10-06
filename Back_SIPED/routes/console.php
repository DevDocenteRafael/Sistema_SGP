<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SPEC 08: backup diário do banco (requer o agendador: php artisan schedule:run a cada minuto).
if (config('producao.backup.agendado')) {
    Schedule::command('siped:backup')->dailyAt((string) config('producao.backup.horario', '02:00'))->withoutOverlapping();
}
