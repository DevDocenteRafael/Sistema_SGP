<?php

return [
    'status' => [
        'Planejado',
        'Realizado',
        'Cancelado',
    ],
    'anos' => ['2024', '2025', '2026', '2027'],
    'possui_acao_extensiva' => [
        'Sim',
        'Não',
    ],
    'eixos' => require __DIR__.'/eixos.php',

    // Tipo do evento: texto controlado enquanto o catálogo oficial não é definido.
    // Estes são só sugestões; o formulário também oferece os tipos já usados.
    'tipos_sugeridos' => [
        'Jornada',
        'Palestra',
        'Oficina',
        'Seminário',
        'Feira',
        'Formatura',
        'Reunião',
    ],
];
