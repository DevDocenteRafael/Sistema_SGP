<?php

return [
    'status' => [
        'Pendente',
        'Em andamento',
        'Realizada',
        'Cancelada',
        'Atrasada',
    ],
    'anos' => ['2024', '2025', '2026', '2027'],
    'prazos' => [
        ['value' => 'dentro', 'label' => 'Dentro do prazo'],
        ['value' => 'fora', 'label' => 'Fora do prazo'],
    ],
    'eixos' => require __DIR__.'/eixos.php',
];
