<?php

/**
 * Configurações de produção do SIPED (SPEC 08 — hardening).
 * Checklist completo: docs/producao.md
 */
return [
    // Redireciona HTTP para HTTPS e gera URLs https. Padrão: ligado em APP_ENV=production.
    'forcar_https' => (bool) env('FORCE_HTTPS', env('APP_ENV') === 'production'),

    // Proxies confiáveis (balanceador/IIS/nginx na frente do PHP). Ex.: "10.0.0.10,10.0.0.11" ou "*".
    'proxies_confiaveis' => env('TRUSTED_PROXIES'),

    // Limites de chamadas por usuário autenticado.
    'limites' => [
        'api_por_minuto' => (int) env('API_LIMITE_POR_MINUTO', 300),
        'pesado_por_minuto' => (int) env('API_LIMITE_PESADO_POR_MINUTO', 20),
    ],

    'backup' => [
        // Pasta dos backups (fora de public/). Padrão: storage/app/backups.
        'pasta' => env('BACKUP_PASTA', storage_path('app/backups')),
        // Executável do mysqldump. Ex. (XAMPP): C:\xampp\mysql\bin\mysqldump.exe
        'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
        // Quantos dias manter os backups.
        'dias_retencao' => (int) env('BACKUP_DIAS_RETENCAO', 30),
        // Backup diário pelo agendador (php artisan schedule:run a cada minuto).
        'agendado' => (bool) env('BACKUP_AGENDADO', true),
        'horario' => env('BACKUP_HORARIO', '02:00'),
    ],
];
