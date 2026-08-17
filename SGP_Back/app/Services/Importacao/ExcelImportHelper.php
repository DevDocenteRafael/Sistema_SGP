<?php

namespace App\Services\Importacao;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait ExcelImportHelper
{
    protected function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }

    protected function cellString(Worksheet $sheet, int $col, int $row): string
    {
        $coord = Coordinate::stringFromColumnIndex($col).$row;
        $valor = $sheet->getCell($coord)->getValue();

        return is_scalar($valor) ? trim((string) $valor) : '';
    }

    protected function cellValue(Worksheet $sheet, int $col, int $row): mixed
    {
        $coord = Coordinate::stringFromColumnIndex($col).$row;

        return $sheet->getCell($coord)->getCalculatedValue();
    }

    protected function localizarAbaPorNomes(Spreadsheet $spreadsheet, array $nomes): ?Worksheet
    {
        $nomesNorm = array_map(fn ($n) => $this->normalizarTexto($n), $nomes);

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (in_array($this->normalizarTexto($sheet->getTitle()), $nomesNorm, true)) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * Quando há várias abas com o mesmo nome normalizado, escolhe a mais completa.
     *
     * @param  list<string>  $nomes
     * @param  list<string>  $colunasPreferidas  chaves do mapa (ex.: codigo, turmas)
     * @param  array<string, list<string>>  $aliases
     */
    protected function localizarMelhorAbaPorNomes(
        Spreadsheet $spreadsheet,
        array $nomes,
        array $colunasPreferidas = [],
        array $aliases = [],
        array $headerMarkers = []
    ): ?Worksheet {
        $nomesNorm = array_map(fn ($n) => $this->normalizarTexto($n), $nomes);
        $candidatas = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (! in_array($this->normalizarTexto($sheet->getTitle()), $nomesNorm, true)) {
                continue;
            }
            $candidatas[] = $sheet;
        }

        if ($candidatas === []) {
            return null;
        }

        if (count($candidatas) === 1 || $colunasPreferidas === [] || $aliases === []) {
            return $candidatas[0];
        }

        $melhor = null;
        $melhorScore = -1;

        foreach ($candidatas as $sheet) {
            $headerRow = $this->encontrarLinhaCabecalho($sheet, $headerMarkers, 2);
            $mapa = $this->mapearColunas($sheet, $headerRow, $aliases);
            $score = 0;
            foreach ($colunasPreferidas as $campo) {
                if (isset($mapa[$campo])) {
                    $score += 10;
                }
            }
            // Desempate: aba com mais linhas de dados
            $score += min(5, (int) floor(((int) $sheet->getHighestDataRow()) / 100));

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhor = $sheet;
            }
        }

        return $melhor ?? $candidatas[0];
    }

    /**
     * @param  list<string>  $markers
     */
    protected function encontrarLinhaCabecalho(Worksheet $sheet, array $markers, int $minMatch = 2): int
    {
        $highestRow = min(20, max(1, (int) $sheet->getHighestDataRow()));
        $markersNorm = array_map(fn ($m) => $this->normalizarTexto($m), $markers);

        for ($row = 1; $row <= $highestRow; $row++) {
            $valores = [];
            $colCount = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($row));

            for ($col = 1; $col <= min($colCount, 40); $col++) {
                $valores[] = $this->normalizarTexto($this->cellString($sheet, $col, $row));
            }

            $achados = 0;
            foreach ($markersNorm as $marker) {
                foreach ($valores as $valor) {
                    if ($valor !== '' && ($valor === $marker || str_contains($valor, $marker))) {
                        $achados++;
                        break;
                    }
                }
            }

            if ($achados >= $minMatch) {
                return $row;
            }
        }

        return 1;
    }

    /**
     * @param  array<string, list<string>>  $aliases
     * @return array<string, int>
     */
    protected function mapearColunas(Worksheet $sheet, int $headerRow, array $aliases): array
    {
        $mapa = [];
        $colCount = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($headerRow));
        $headers = [];

        for ($col = 1; $col <= min($colCount, 40); $col++) {
            $header = $this->normalizarTexto($this->cellString($sheet, $col, $headerRow));
            if ($header !== '') {
                $headers[$col] = $header;
            }
        }

        // 1º: match exato (evita "quantidade de cursos" roubar o campo "cursos")
        foreach ($aliases as $campo => $lista) {
            foreach ($lista as $alias) {
                $aliasNorm = $this->normalizarTexto($alias);
                if ($aliasNorm === '') {
                    continue;
                }
                foreach ($headers as $col => $header) {
                    if ($header === $aliasNorm) {
                        $mapa[$campo] = $col;
                        break 2;
                    }
                }
            }
        }

        // 2º: contains só para campos ainda sem coluna
        foreach ($aliases as $campo => $lista) {
            if (isset($mapa[$campo])) {
                continue;
            }
            foreach ($lista as $alias) {
                $aliasNorm = $this->normalizarTexto($alias);
                if ($aliasNorm === '' || mb_strlen($aliasNorm) < 3) {
                    continue;
                }
                foreach ($headers as $col => $header) {
                    if (str_contains($header, $aliasNorm)) {
                        $mapa[$campo] = $col;
                        break 2;
                    }
                }
            }
        }

        return $mapa;
    }

    protected function parseData(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor))->format('Y-m-d');
            }

            $texto = trim((string) $valor);
            if ($texto === '') {
                return null;
            }

            // ISO direto
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $texto)) {
                return Carbon::createFromFormat('!Y-m-d', substr($texto, 0, 10))->format('Y-m-d');
            }

            // dd/mm/yyyy ou d/m/yy — assume formato brasileiro
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $texto, $m)) {
                $dia = (int) $m[1];
                $mes = (int) $m[2];
                $ano = (int) $m[3];
                if ($ano < 100) {
                    $ano += $ano >= 70 ? 1900 : 2000;
                }
                if (checkdate($mes, $dia, $ano)) {
                    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                }
            }

            $dt = Carbon::parse($texto);
            if ($dt->year >= 1990 && $dt->year <= 2100) {
                return $dt->format('Y-m-d');
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function textoOuNulo(mixed $valor): ?string
    {
        if (! is_scalar($valor)) {
            return null;
        }
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    protected function valorVazio(mixed $valor): bool
    {
        return $valor === null || $valor === '';
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    protected function linhaVazia(array $registro): bool
    {
        foreach ($registro as $valor) {
            if (! $this->valorVazio($valor)) {
                return false;
            }
        }

        return true;
    }
}
