<?php

namespace App\Services\Importacao;

use App\Services\CadastroAuditoriaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportacaoService
{
    use ExcelImportHelper;

    public function __construct(
        private readonly ImportBackupService $backupService,
    ) {
    }

    public function catalogo(): array
    {
        return array_values(config('importacoes.catalogo', []));
    }

    public function existe(string $modulo): bool
    {
        return array_key_exists($modulo, config('importacoes.catalogo', []));
    }

    public function definicao(string $modulo): array
    {
        if (! $this->existe($modulo)) {
            throw new InvalidArgumentException("Módulo de importação inválido: {$modulo}");
        }

        return config("importacoes.catalogo.{$modulo}");
    }

    /**
     * @return array{
     *   linhas: list<array<string, mixed>>,
     *   erros: list<array{linha: int, mensagem: string}>,
     *   total: int,
     *   ignoradas: int,
     *   aba: string,
     *   colunas_preview: list<array{key: string, label: string}>,
     *   label: string
     * }
     */
    public function parse(string $modulo, UploadedFile $arquivo): array
    {
        $def = $this->definicao($modulo);
        $spreadsheet = IOFactory::load($arquivo->getRealPath());

        $resultado = match ($def['mode']) {
            'multi_sheet' => $this->parseMultiSheet($spreadsheet, $def),
            'eixos_forward_fill' => $this->parseEixosForwardFill($spreadsheet, $def),
            default => $this->parseSingleSheet($spreadsheet, $def),
        };

        // Plano de metas: coluna sem nome (curso) fica na 3ª coluna após segmento
        if ($modulo === 'plano-de-metas') {
            $resultado = $this->enriquecerPlanoDeMetas($spreadsheet, $def, $resultado);
        }

        $resultado['colunas_preview'] = $def['preview_columns'] ?? [];
        $resultado['label'] = $def['label'];

        return $resultado;
    }

    public function commit(string $modulo, UploadedFile $arquivo): array
    {
        $def = $this->definicao($modulo);
        $resultado = $this->parse($modulo, $arquivo);

        if ($resultado['total'] === 0) {
            throw new InvalidArgumentException(
                'Nenhuma linha válida encontrada para importar em '.$def['label'].'.'
            );
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $def['model'];
        $campos = $def['db_fields'];
        $agora = now();

        $camposUnicos = $def['unique_fields'] ?? [];
        $defaults = $def['defaults'] ?? [];

        $usuarioId = Auth::id();
        $backup = null;

        DB::transaction(function () use ($modelClass, $campos, $resultado, $agora, $modulo, $camposUnicos, $defaults, $usuarioId, $def, &$backup) {
            $backup = $this->backupService->backupAntesDeSubstituir($modulo, $modelClass);

            $modelClass::query()->delete();

            $lote = [];
            $vistos = [];
            foreach ($resultado['linhas'] as $linha) {
                $row = [];
                foreach ($campos as $campo) {
                    $valor = $linha[$campo] ?? null;
                    if ($campo === 'ano' && $valor !== null && $valor !== '') {
                        $valor = (int) preg_replace('/\D+/', '', (string) $valor) ?: null;
                    }
                    if ($campo === 'quantidade_pessoas' && $valor !== null && $valor !== '') {
                        $valor = (int) preg_replace('/\D+/', '', (string) $valor) ?: null;
                    }
                    if ($campo === 'ativo' && ($valor === null || $valor === '') && $modulo === 'horas-pedagogicas') {
                        $valor = true;
                    }
                    if (is_string($valor)) {
                        $valor = trim($valor);
                        if ($valor === '') {
                            $valor = null;
                        }
                    }
                    // Campos UNIQUE: placeholders da planilha (-, em criação…) viram null
                    if (in_array($campo, $camposUnicos, true)) {
                        $valor = $this->normalizarValorUnico($valor);
                    }
                    // Defaults (ex.: cursos.status → ATIVO quando a aba não tem Status SIG)
                    if (($valor === null || $valor === '') && array_key_exists($campo, $defaults)) {
                        $valor = $defaults[$campo];
                    }
                    $row[$campo] = $valor;
                }

                // Se o valor UNIQUE já apareceu, zera o campo (mantém a linha)
                foreach ($camposUnicos as $campoUnico) {
                    $val = $row[$campoUnico] ?? null;
                    if ($val === null || $val === '') {
                        $row[$campoUnico] = null;
                        continue;
                    }
                    $chave = $campoUnico.'|'.mb_strtolower((string) $val);
                    if (isset($vistos[$chave])) {
                        $row[$campoUnico] = null;
                        continue;
                    }
                    $vistos[$chave] = true;
                }

                $row['created_at'] = $agora;
                $row['updated_at'] = $agora;
                if ($usuarioId) {
                    $row['criado_por'] = $usuarioId;
                    $row['atualizado_por'] = $usuarioId;
                }
                $lote[] = $row;
            }

            foreach (array_chunk($lote, 200) as $chunk) {
                $modelClass::query()->insert($chunk);
            }

            app(CadastroAuditoriaService::class)->registrar(
                CadastroAuditoriaService::ACAO_IMPORTAR,
                $modulo,
                null,
                'Importou '.count($lote).' registro(s) em '.($def['label'] ?? $modulo),
                [
                    'total' => count($lote),
                    'ignoradas' => $resultado['ignoradas'] ?? 0,
                    'aba' => $resultado['aba'] ?? null,
                    'backup' => $backup,
                ],
            );
        });

        $resultado['total'] = $modelClass::query()->count();
        $resultado['backup'] = $backup;

        return $resultado;
    }

    /**
     * Converte placeholders comuns de planilha em null para campos UNIQUE.
     */
    private function normalizarValorUnico(mixed $valor): mixed
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = mb_strtolower(trim((string) $valor), 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        $placeholders = [
            '-', '--', '—', '–', '?', 'x', 'n/a', 'na', 's/n', 'sn',
            'em criacao', 'em criação', 'em elaboracao', 'em elaboração',
            'nao possui', 'não possui', 'sem codigo', 'sem código',
            'null', 'none', '#n/d', 'n.d.', 'nd', '.',
        ];

        if (in_array($texto, $placeholders, true)) {
            return null;
        }

        return $valor;
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}
     */
    private function parseSingleSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $def): array
    {
        $sheet = $this->localizarAbaPorNomes($spreadsheet, $def['sheet_names'] ?? []);
        if (! $sheet) {
            $esperada = $def['sheet_names'][0] ?? $def['label'];
            throw new InvalidArgumentException(
                'Aba "'.$esperada.'" não encontrada no arquivo. Envie uma planilha que contenha essa aba.'
            );
        }

        return $this->parseSheet($sheet, $def, $sheet->getTitle());
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}
     */
    private function parseMultiSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $def): array
    {
        $nomes = array_map(fn ($n) => $this->normalizarTexto($n), $def['sheet_names'] ?? []);
        $linhas = [];
        $erros = [];
        $ignoradas = 0;
        $abasUsadas = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $tituloNorm = $this->normalizarTexto($sheet->getTitle());
            if (! in_array($tituloNorm, $nomes, true)) {
                continue;
            }

            $parcial = $this->parseSheet($sheet, $def, $sheet->getTitle());
            $abasUsadas[] = $sheet->getTitle();

            foreach ($parcial['linhas'] as &$linha) {
                // Cursos: o filtro da UI usa o nome da aba do portfólio
                // (ex.: "Gastronomia e Turismo"), não o Segmento interno (ex.: "GASTRONOMIA").
                if (($def['key'] ?? '') === 'cursos') {
                    $linha['eixo'] = $sheet->getTitle();
                } elseif ($this->valorVazio($linha['eixo'] ?? null)) {
                    $linha['eixo'] = $sheet->getTitle();
                }
            }
            unset($linha);

            $linhas = array_merge($linhas, $parcial['linhas']);
            $erros = array_merge($erros, $parcial['erros']);
            $ignoradas += $parcial['ignoradas'];
        }

        if ($abasUsadas === []) {
            throw new InvalidArgumentException(
                'Nenhuma aba de portfólio de '.$def['label'].' foi encontrada no arquivo.'
            );
        }

        return [
            'linhas' => $linhas,
            'erros' => $erros,
            'total' => count($linhas),
            'ignoradas' => $ignoradas,
            'aba' => implode(', ', $abasUsadas),
        ];
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}
     */
    private function parseEixosForwardFill(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $def): array
    {
        // Há duas abas com nome quase idêntico: resumo (~26 linhas) e detalhada (~540).
        // Preferir a que tiver colunas operacionais (código/turmas/alunos).
        $sheet = $this->localizarMelhorAbaPorNomes(
            $spreadsheet,
            $def['sheet_names'] ?? [],
            ['codigo', 'turmas', 'alunos', 'ch'],
            $def['columns'] ?? [],
            $def['header_markers'] ?? []
        );
        if (! $sheet) {
            $esperada = $def['sheet_names'][0] ?? $def['label'];
            throw new InvalidArgumentException(
                'Aba "'.$esperada.'" não encontrada no arquivo. Envie uma planilha que contenha essa aba.'
            );
        }

        $headerRow = $this->encontrarLinhaCabecalho($sheet, $def['header_markers'] ?? [], 2);
        $mapa = $this->mapearColunas($sheet, $headerRow, $def['columns'] ?? []);

        // Aba-resumo só tem Segmento/Eixo/Quantidade/Cursos — sem turmas/código.
        if (! isset($mapa['codigo']) && ! isset($mapa['turmas']) && ! isset($mapa['alunos'])) {
            throw new InvalidArgumentException(
                'A aba "'.$sheet->getTitle().'" parece ser só o resumo por eixo. Use a aba detalhada “Quantidade de cursos por eixo” (com colunas Código, Turmas e Alunos).'
            );
        }

        $linhas = [];
        $erros = [];
        $ignoradas = 0;
        $carry = ['eixo' => null, 'curso' => null, 'ch' => null];
        $highestRow = (int) $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $registro = $this->lerRegistro($sheet, $row, $mapa, $def['date_fields'] ?? [], array_keys($def['columns'] ?? []));

            foreach (['eixo', 'curso', 'ch'] as $campo) {
                if (! $this->valorVazio($registro[$campo] ?? null)) {
                    $carry[$campo] = $registro[$campo];
                } else {
                    $registro[$campo] = $carry[$campo];
                }
            }

            if ($this->linhaVazia($registro)) {
                $ignoradas++;
                continue;
            }

            // Linhas só de agrupamento (sem código/turma/alunos)
            if ($this->valorVazio($registro['codigo'] ?? null) && $this->valorVazio($registro['turmas'] ?? null) && $this->valorVazio($registro['alunos'] ?? null)) {
                $ignoradas++;
                continue;
            }

            if (! $this->temCampoObrigatorio($registro, $def['required_any'] ?? [])) {
                $erros[] = ['linha' => $row, 'mensagem' => 'Linha sem campos obrigatórios.'];
                $ignoradas++;
                continue;
            }

            $linhas[] = $registro;
        }

        return [
            'linhas' => $linhas,
            'erros' => $erros,
            'total' => count($linhas),
            'ignoradas' => $ignoradas,
            'aba' => $sheet->getTitle(),
        ];
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}
     */
    private function parseSheet(Worksheet $sheet, array $def, string $abaLabel): array
    {
        $headerRow = $this->encontrarLinhaCabecalho($sheet, $def['header_markers'] ?? [], 2);
        $mapa = $this->mapearColunas($sheet, $headerRow, $def['columns'] ?? []);

        // Plano de metas: se "curso" não mapeou, usa coluna C (3) quando segmento está em B
        if (($def['key'] ?? '') === 'plano-de-metas' && ! isset($mapa['curso']) && isset($mapa['segmento'])) {
            $mapa['curso'] = $mapa['segmento'] + 1;
        }

        if (! $this->mapaTemObrigatorio($mapa, $def['required_any'] ?? [])) {
            throw new InvalidArgumentException(
                'Não foi possível mapear as colunas obrigatórias na aba "'.$sheet->getTitle().'".'
            );
        }

        $campos = array_keys($def['columns'] ?? []);
        $linhas = [];
        $erros = [];
        $ignoradas = 0;
        $highestRow = (int) $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $registro = $this->lerRegistro($sheet, $row, $mapa, $def['date_fields'] ?? [], $campos);

            // Horas: copiar segmento → eixo se eixo vazio
            if (($def['key'] ?? '') === 'horas-pedagogicas' && $this->valorVazio($registro['eixo'] ?? null)) {
                $registro['eixo'] = $registro['segmento'] ?? null;
            }

            if ($this->linhaVazia($registro)) {
                $ignoradas++;
                continue;
            }

            if (! $this->temCampoObrigatorio($registro, $def['required_any'] ?? [])) {
                $erros[] = [
                    'linha' => $row,
                    'mensagem' => 'Linha sem campos obrigatórios para '.$def['label'].'.',
                ];
                $ignoradas++;
                continue;
            }

            // Normaliza CH (200h → 200)
            if (isset($registro['carga_horaria']) && is_string($registro['carga_horaria'])) {
                $registro['carga_horaria'] = trim(str_ireplace(['h', 'horas'], '', $registro['carga_horaria']));
            }
            if (isset($registro['ch']) && is_string($registro['ch'])) {
                $registro['ch'] = trim(str_ireplace(['h', 'horas'], '', $registro['ch']));
            }

            $linhas[] = $registro;
        }

        return [
            'linhas' => $linhas,
            'erros' => $erros,
            'total' => count($linhas),
            'ignoradas' => $ignoradas,
            'aba' => $abaLabel,
        ];
    }

    /**
     * @param  array<string, int>  $mapa
     * @param  list<string>  $dateFields
     * @param  list<string>  $campos
     * @return array<string, mixed>
     */
    private function lerRegistro(Worksheet $sheet, int $row, array $mapa, array $dateFields, array $campos): array
    {
        $registro = [];
        foreach ($campos as $campo) {
            $registro[$campo] = null;
        }

        foreach ($mapa as $campo => $col) {
            $valor = $this->cellValue($sheet, $col, $row);
            if (in_array($campo, $dateFields, true)) {
                $registro[$campo] = $this->parseData($valor);
            } else {
                $registro[$campo] = $this->textoOuNulo($valor);
            }
        }

        return $registro;
    }

    /**
     * @param  list<string>  $requiredAny
     * @param  array<string, int>  $mapa
     */
    private function mapaTemObrigatorio(array $mapa, array $requiredAny): bool
    {
        if ($requiredAny === []) {
            return true;
        }
        foreach ($requiredAny as $campo) {
            if (isset($mapa[$campo])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $registro
     * @param  list<string>  $requiredAny
     */
    private function temCampoObrigatorio(array $registro, array $requiredAny): bool
    {
        if ($requiredAny === []) {
            return true;
        }
        foreach ($requiredAny as $campo) {
            if (! $this->valorVazio($registro[$campo] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fallback extra para plano de metas se o curso ainda estiver vazio.
     *
     * @param  array<string, mixed>  $def
     * @param  array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}  $resultado
     * @return array{linhas: list<array<string, mixed>>, erros: list<array{linha: int, mensagem: string}>, total: int, ignoradas: int, aba: string}
     */
    private function enriquecerPlanoDeMetas(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $def, array $resultado): array
    {
        // Já tratado no parseSheet via coluna adjacente; mantém hook para futuras regras.
        unset($spreadsheet, $def);

        return $resultado;
    }
}
