<?php

/**
 * Horas Pedagógicas (SPEC 06).
 */
return [
    // Status principais confirmados na reunião.
    'status' => [
        'Pendente',
        'Em andamento',
        'Concluída',
    ],
    // Status legado: continua aceito em registros que já o têm, mas não é oferecido
    // para novos registros até o cliente decidir se ele deve existir.
    'status_legados' => [
        'Cancelada',
    ],
    'anos' => ['2024', '2025', '2026', '2027'],
    'eixos' => require __DIR__.'/eixos.php',
];
