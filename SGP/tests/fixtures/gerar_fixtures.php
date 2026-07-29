<?php

/**
 * Gera fixtures Excel para testes de importação.
 * Uso: php tests/fixtures/gerar_fixtures.php
 */

require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function setCell($sheet, int $col, int $row, mixed $val): void
{
    $coord = Coordinate::stringFromColumnIndex($col).$row;
    if ($val === null) {
        return;
    }
    // Força texto para não perder zeros em SEI (2026.200 → 2026.2) nem reinterpretar datas
    $sheet->setCellValueExplicit($coord, (string) $val, DataType::TYPE_STRING);
}

function salvar(Spreadsheet $ss, string $nome): void
{
    $path = __DIR__.'/'.$nome;
    (new Xlsx($ss))->save($path);
    echo "OK {$nome}\n";
}

function sheetComCabecalho(Spreadsheet $ss, string $titulo, array $headers, array $rows, int $index = 0): void
{
    if ($index === 0) {
        $sheet = $ss->getActiveSheet();
    } else {
        $sheet = $ss->createSheet($index);
    }
    $sheet->setTitle($titulo);
    foreach ($headers as $i => $h) {
        setCell($sheet, $i + 1, 1, $h);
    }
    foreach ($rows as $r => $row) {
        foreach ($row as $c => $val) {
            setCell($sheet, $c + 1, $r + 2, $val);
        }
    }
}

// --- Cursos (multi sheet) ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Saúde', [
    'Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG', 'Tipo', 'Processo SEI', 'Unidade que pode ser rodado',
], [
    ['ATIVO', 'Saúde', 'Presencial', 'Cuidador de Idosos', '160', 'SIG-001', 'FIC', '2026.111', 'Taguatinga'],
    ['ATIVO', 'Saúde', 'Presencial', 'Primeiros Socorros', '40', 'SIG-002', 'FIC', '2026.112', 'Asa Norte'],
]);
sheetComCabecalho($ss, 'Gestão e Moda', [
    'Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG', 'Tipo', 'Processo SEI', 'Unidade que pode ser rodado',
], [
    ['ATIVO', 'Gestão e Moda', 'EaD', 'Gestão de Pessoas', '200', 'SIG-010', 'Técnico', '2026.113', 'Ceilândia'],
], 1);
salvar($ss, 'cursos-sample.xlsx');

// --- Cursos sem coluna Status SIG (como aba Saúde) ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Saúde', [
    'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG', 'Tipo', 'Processo SEI',
], [
    ['Saúde', 'Presencial', 'Cuidador de Idoso', '160', '68007', 'FIC', '2023.000001667-89'],
]);
salvar($ss, 'cursos-sem-status.xlsx');

// --- Plano de Metas ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'PLANO DE METAS 2025', [
    'SEGMENTO', 'CURSO', 'TIPO', 'NÚMERO SEI', 'CÓDIGO SIG', 'MÊS DE ENTREGA', 'STATUS', 'ORIGEM', 'OBSERVAÇÃO', 'STATUS FINAL',
], [
    ['Saúde', 'Cuidador de Idosos', 'Revisão', '2026.200', 'SIG-001', 'Março', 'Em andamento', 'PCA', 'Obs 1', 'OK'],
    ['Gestão', 'Gestão de Pessoas', 'Novo', '2026.201', 'SIG-010', 'Abril', 'Concluído', 'Demanda', null, 'OK'],
]);
salvar($ss, 'plano-de-metas-sample.xlsx');

// --- PCA ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'PCA 2026 | Propostas', [
    'Título - Nome do Curso', 'Segmento', 'CH', 'Cód. SIG', 'Processo SEI', 'Status', 'Precificação', 'Unidade', 'Ano',
], [
    ['Curso PCA A', 'Saúde', '160', 'PCA-SIG-1', '2026.301', 'Proposto', 'R$ 1.200', 'Taguatinga', '2026'],
    ['Curso PCA B', 'Gestão', '200', 'PCA-SIG-2', '2026.302', 'Aprovado', 'R$ 2.000', 'Asa Norte', '2026'],
]);
salvar($ss, 'pcas-sample.xlsx');

// --- Eixos ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Quantidade de cursos por eixo', [
    'Segmento', 'Cursos', 'CH do curso', 'Turmas', 'Código', 'Alunos', 'Instrutores',
], [
    ['Saúde', 'Cuidador', '160', '2', 'EIX-1', '40', 'João'],
    [null, 'Primeiros Socorros', '40', '1', 'EIX-2', '20', 'Maria'],
]);
salvar($ss, 'eixos-sample.xlsx');

// --- Visitas ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Processos de Visitas Técnicas', [
    'Relação dos CEPs', 'Processo SEI', 'Observação', 'Eixo', 'Status', 'Responsável',
], [
    ['Taguatinga', '2026.401', 'Visita laboratório', 'Saúde', 'Agendada', 'Ana'],
    ['Asa Norte', '2026.402', null, 'Gestão', 'Concluída', 'Bruno'],
]);
salvar($ss, 'visitas-sample.xlsx');

// --- Horas ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Processos Horas Pedagógicas', [
    'Processo SEI', 'Segmentos', 'Pessoa', 'Matrícula', 'Ano', 'Motivo', 'Status', 'Observação',
], [
    ['2026.501', 'Saúde', 'Carla', 'M-1', '2026', 'Planejamento', 'Aberto', null],
    ['2026.502', 'Gestão', 'Diego', 'M-2', '2026', 'Avaliação', 'Fechado', 'Ok'],
]);
salvar($ss, 'horas-sample.xlsx');

// --- Eventos ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Eventos', [
    'Nome', 'Ano', 'Data', 'Unidade', 'Eixo', 'Quantidade Pessoas', 'Equipe', 'Status', 'Observação',
], [
    ['Feira de Profissões', '2026', '15/03/2026', 'Taguatinga', 'Saúde', '120', 'CPED', 'Confirmado', null],
    ['Semana da Tecnologia', '2026', '20/05/2026', 'Asa Norte', 'Tecnologia', '80', 'CPED', 'Planejado', 'Obs'],
]);
salvar($ss, 'eventos-sample.xlsx');

// --- Plano de Metas com codigo_sig placeholder duplicado (-) ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'PLANO DE METAS 2025', [
    'SEGMENTO', 'CURSO', 'TIPO', 'NÚMERO SEI', 'CÓDIGO SIG', 'MÊS DE ENTREGA', 'STATUS', 'ORIGEM',
], [
    ['Saúde', 'Curso A', 'FIC', '2026.900', '-', 'Março', 'CPED', 'Plano de Metas'],
    ['Gestão', 'Curso B', 'FIC', '2026.901', '-', 'Abril', 'CPED', 'Plano de Metas'],
]);
salvar($ss, 'plano-de-metas-sig-dup.xlsx');

// --- Eixos: workbook com aba-resumo E aba detalhada (mesmo nome normalizado) ---
$ss = new Spreadsheet;
sheetComCabecalho($ss, 'Quantidade de Cursos por Eixo ', [
    'Segmento', 'Eixo', 'Quantidade de Cursos', 'Cursos',
], [
    ['Saúde', 'Enfermagem', '2', "Cuidador\nPrimeiros Socorros"],
    [null, 'Nutrição', '1', 'Nutrição Clínica'],
]);
sheetComCabecalho($ss, 'Quantidade de cursos por eixo', [
    'SEGMENTO', 'Quantidade de Cursos', 'Cursos', 'CH do curso', 'Turmas (2º Semestre)', 'Codigo', 'Alunos (Matriculas)', 'instrutores',
], [
    ['Saúde', '2', 'Cuidador', '160', '2', 'EIX-10', '40', 'João'],
    [null, null, null, null, '1', 'EIX-11', '20', 'Maria'],
    ['Gestão', '1', 'Gestão de Pessoas', '200', '1', 'EIX-20', '30', 'Ana'],
], 1);
salvar($ss, 'eixos-duas-abas.xlsx');

echo "Fixtures geradas.\n";
