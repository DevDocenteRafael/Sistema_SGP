<?php

/**
 * Limiares de saúde operacional (disco).
 * Usados pelo comando artisan e documentados no script PowerShell externo.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Unidades monitoradas
    |--------------------------------------------------------------------------
    |
    | Lista de letras de unidade (Windows) ou caminhos. Vazio = unidade do
    | storage_path() da aplicação.
    |
    */
    'disks' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SGP_HEALTH_DISKS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Aviso (warning)
    |--------------------------------------------------------------------------
    |
    | Dispara quando o livre for MENOR que percentual OU menor que GB.
    | Exit code sugerido no script externo: 1
    |
    */
    'warning' => [
        'free_percent' => (float) env('SGP_DISK_WARN_PERCENT', 15),
        'free_gb' => (float) env('SGP_DISK_WARN_GB', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crítico
    |--------------------------------------------------------------------------
    |
    | Exit code sugerido no script externo: 2
    |
    */
    'critical' => [
        'free_percent' => (float) env('SGP_DISK_CRIT_PERCENT', 8),
        'free_gb' => (float) env('SGP_DISK_CRIT_GB', 8),
    ],
];
