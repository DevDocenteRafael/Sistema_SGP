<?php

/**
 * Ações Extensivas (SPEC 06): prioridade, setor/etapa e status são coisas diferentes.
 */
return [
    // Prioridade: somente Baixa, Média e Alta.
    'priorizacoes' => [
        'Baixa',
        'Média',
        'Alta',
    ],

    // Setor/etapa em que a ação está (antes gravado como "status").
    'setores' => [
        'CPED',
        'DEP',
        'DIREG',
        'NC',
    ],

    // Status de execução: PENDENTE de validação da nomenclatura com o cliente.
    // Sugestão da reunião: Pendente, Em andamento, Concluída, Cancelada. Enquanto vazio,
    // o campo não aparece no formulário e não é exigido.
    'status_execucao' => [],

    'tipos' => [
        'Ação Extensiva',
    ],
    'eixos' => require __DIR__.'/eixos.php',
];
