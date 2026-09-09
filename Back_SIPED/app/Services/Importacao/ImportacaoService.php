<?php

namespace App\Services\Importacao;

use App\Exceptions\ImportacaoInvalidaException;
use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\PortfolioCiclo;
use App\Services\CadastroAuditoriaService;
use App\Support\CatalogoInstitucional;
use App\Support\CatalogoOficial;
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

        if (($def['key'] ?? $modulo) === 'cursos') {
            $resultado = $this->validarCatalogoCursos($resultado);
        } elseif (($def['key'] ?? $modulo) === 'eixos') {
            $resultado = $this->validarOfertasEixos($resultado);
        } else {
            $resultado = $this->canonicalizarEixosNasLinhas($resultado);
        }

        $resultado = $this->classificarAcoesUpsert($modulo, $def, $resultado);

        return $resultado;
    }

    public function commit(string $modulo, UploadedFile $arquivo): array
    {
        $def = $this->definicao($modulo);
        $resultado = $this->parse($modulo, $arquivo);

        if ($this->temErrosBloqueantes($resultado)) {
            throw new ImportacaoInvalidaException(
                'A importação foi bloqueada: existem valores inválidos na planilha. Corrija-os e envie novamente. Nenhum registro existente foi alterado.',
                $resultado['erros'],
            );
        }

        if ($resultado['total'] === 0) {
            throw new InvalidArgumentException(
                'Nenhuma linha válida encontrada para importar em '.$def['label'].'.'
            );
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $def['model'];
        $campos = $def['db_fields'];

        $camposUnicos = $def['unique_fields'] ?? [];
        $defaults = $def['defaults'] ?? [];

        $usuarioId = Auth::id();
        $backup = null;
        $resumo = ['novo' => 0, 'atualizar' => 0, 'sem_alteracao' => 0];

        $cicloAtualId = in_array($modulo, ['cursos', 'plano-de-metas', 'pcas', 'eixos'], true)
            ? PortfolioCiclo::atual()?->id
            : null;

        DB::transaction(function () use ($modelClass, $campos, $resultado, $modulo, $camposUnicos, $defaults, $usuarioId, $def, $cicloAtualId, &$backup, &$resumo) {
            $backup = $this->backupService->backupAntesDeSubstituir($modulo, $modelClass);

            foreach ($resultado['linhas'] as $linha) {
                if (($linha['status_importacao'] ?? '') === 'erro') {
                    continue;
                }

                $row = $this->montarLinhaCommit($linha, $campos, $camposUnicos, $defaults, $modulo, $cicloAtualId);
                $existente = $this->encontrarExistente($modulo, $modelClass, $row, $cicloAtualId);

                if (! $existente) {
                    if ($usuarioId) {
                        $row['criado_por'] = $usuarioId;
                        $row['atualizado_por'] = $usuarioId;
                    }
                    $modelClass::query()->create($row);
                    $resumo['novo']++;
                    continue;
                }

                if (($linha['status_importacao'] ?? '') === 'sem_alteracao') {
                    $resumo['sem_alteracao']++;
                    continue;
                }

                unset($row['ciclo_id']);
                if ($usuarioId) {
                    $row['atualizado_por'] = $usuarioId;
                }
                $existente->fill($row);
                $existente->save();
                $resumo['atualizar']++;
            }

            app(CadastroAuditoriaService::class)->registrar(
                CadastroAuditoriaService::ACAO_IMPORTAR,
                $modulo,
                null,
                'Importou '.($resumo['novo'] + $resumo['atualizar']).' registro(s) em '.($def['label'] ?? $modulo).' (upsert)',
                [
                    'novo' => $resumo['novo'],
                    'atualizar' => $resumo['atualizar'],
                    'sem_alteracao' => $resumo['sem_alteracao'],
                    'ignoradas' => $resultado['ignoradas'] ?? 0,
                    'aba' => $resultado['aba'] ?? null,
                    'backup' => $backup,
                    'ciclo_id' => $cicloAtualId,
                ],
            );
        });

        $resultado['total'] = $resumo['novo'] + $resumo['atualizar'] + $resumo['sem_alteracao'];
        $resultado['resumo_acoes'] = $resumo;
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
     * Planilha de portfólio às vezes desloca colunas: tipo/unidade caem em
     * compatível com bolsa / comercial. Mantém só SIM/NÃO válidos.
     *
     * @param  array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    private function normalizarCamposCurso(array $registro): array
    {
        foreach (['compativel_bolsa', 'comercial'] as $campo) {
            if (array_key_exists($campo, $registro)) {
                $registro[$campo] = $this->normalizarFlagSimNao($registro[$campo] ?? null);
            }
        }

        foreach (['pcn', 'pcr'] as $campo) {
            if (! array_key_exists($campo, $registro)) {
                continue;
            }

            $flag = $this->normalizarFlagSimNao($registro[$campo] ?? null);
            if ($flag !== null) {
                $registro[$campo] = $flag;
                continue;
            }

            $bruto = is_string($registro[$campo] ?? null) ? trim((string) $registro[$campo]) : null;
            if ($bruto === null || $bruto === '' || in_array($bruto, ['?', '-', '—', '.'], true)) {
                $registro[$campo] = null;
            }
        }

        foreach (['codigo_dn', 'codigo_sig'] as $campo) {
            $valor = $registro[$campo] ?? null;
            if (! is_string($valor)) {
                continue;
            }

            $valor = trim($valor);
            if ($valor === '' || mb_strlen($valor) > 80) {
                // Vazio ou texto de observação que caiu na coluna de código
                $registro[$campo] = null;
                continue;
            }

            $registro[$campo] = mb_substr($valor, 0, 100);
        }

        return $registro;
    }

    private function normalizarFlagSimNao(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $normalizado = mb_strtoupper($texto, 'UTF-8');
        $normalizado = strtr($normalizado, [
            'Ã' => 'A',
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Õ' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
        ]);
        $normalizado = preg_replace('/\s+/u', '', $normalizado) ?? $normalizado;

        if (in_array($normalizado, ['SIM', 'S', 'YES', 'Y', '1', 'TRUE', 'VERDADEIRO'], true)) {
            return 'SIM';
        }

        if (in_array($normalizado, ['NAO', 'N', 'NO', '0', 'FALSE', 'FALSO'], true)) {
            return 'NÃO';
        }

        // Ex.: "Programa Socioprofissional", "NUCOMP", "NC" — coluna errada
        return null;
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
                if (($def['key'] ?? '') === 'cursos') {
                    $linha['_aba'] = $sheet->getTitle();
                    $linha['_linha'] = $linha['_linha'] ?? null;
                    if ($this->valorVazio($linha['segmento'] ?? null) && ! $this->valorVazio($linha['eixo'] ?? null)) {
                        $linha['segmento'] = $linha['eixo'];
                    }
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
        $carry = ['segmento' => null, 'eixo' => null, 'curso' => null, 'ch' => null];
        $highestRow = (int) $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $registro = $this->lerRegistro($sheet, $row, $mapa, $def['date_fields'] ?? [], array_keys($def['columns'] ?? []));

            foreach (['segmento', 'eixo', 'curso', 'ch'] as $campo) {
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

            $registro['_linha'] = $row;
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

            if (($def['key'] ?? '') === 'cursos') {
                $registro = $this->normalizarCamposCurso($registro);
                $registro['_aba'] = $abaLabel;
                $registro['_linha'] = $row;
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
     * @param  array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}  $resultado
     * @return array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}
     */
    private function validarCatalogoCursos(array $resultado): array
    {
        $erros = $resultado['erros'];
        $linhas = [];

        foreach ($resultado['linhas'] as $linha) {
            $aba = (string) ($linha['_aba'] ?? $resultado['aba'] ?? '');
            $numeroLinha = (int) ($linha['_linha'] ?? 0);

            $eixoBruto = is_scalar($linha['eixo'] ?? null) ? trim((string) $linha['eixo']) : '';
            $segmentoBruto = is_scalar($linha['segmento'] ?? null) ? trim((string) $linha['segmento']) : '';
            $resolvido = CatalogoOficial::resolverEixoESegmento(
                $eixoBruto !== '' ? $eixoBruto : null,
                $segmentoBruto !== '' ? $segmentoBruto : null,
            );

            if ($resolvido['erro'] !== null) {
                $valor = $resolvido['segmento'] ?? $segmentoBruto ?: $eixoBruto;
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    str_contains((string) $resolvido['erro'], 'Segmento') ? 'Segmento' : 'Eixo',
                    (string) $valor,
                    $resolvido['erro'],
                );
                $linha['status_importacao'] = 'erro';
            } else {
                $linha['eixo'] = $resolvido['eixo'];
                $linha['segmento'] = $resolvido['segmento'];
                $linha['programa'] = $resolvido['programa'] ?? null;
                $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
                $linha['eixo_id'] = $ids['eixo_id'];
                $linha['segmento_id'] = $ids['segmento_id'];
            }

            $resolucao = CatalogoOficial::resolverModalidadeImportacao(
                $linha['modalidade'] ?? null,
                $linha['tipo'] ?? null,
            );
            $linha['modalidade'] = $resolucao['modalidade'];
            $linha['tipo'] = $resolucao['tipo'];
            if ($resolucao['erro'] !== null) {
                $valorModalidade = is_scalar($linha['modalidade'] ?? null)
                    ? (string) ($linha['modalidade'] ?? '')
                    : '';
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    'Modalidade',
                    $valorModalidade !== '' ? $valorModalidade : (string) ($linha['tipo'] ?? ''),
                    $resolucao['erro'],
                );
                $linha['status_importacao'] = 'erro';
            }

            unset($linha['_aba'], $linha['_linha']);
            $linhas[] = $linha;
        }

        $resultado['linhas'] = $linhas;
        $resultado['erros'] = $erros;
        $resultado['total'] = count($linhas);

        return $resultado;
    }

    /**
     * @param  array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}  $resultado
     * @return array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}
     */
    private function canonicalizarEixosNasLinhas(array $resultado): array
    {
        foreach ($resultado['linhas'] as &$linha) {
            foreach (['eixo', 'segmento'] as $campo) {
                if (! array_key_exists($campo, $linha) || $this->valorVazio($linha[$campo] ?? null)) {
                    continue;
                }

                $canon = CatalogoOficial::canonicalizarEixo((string) $linha[$campo]);
                if ($canon !== null) {
                    $linha[$campo] = $canon;
                }
            }
        }
        unset($linha);

        return $resultado;
    }

    /**
     * @param  array{erros?: list<array<string, mixed>>}  $resultado
     */
    private function temErrosBloqueantes(array $resultado): bool
    {
        foreach ($resultado['erros'] ?? [] as $erro) {
            if (($erro['bloqueante'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{aba: string, linha: int, coluna: string, valor: string, mensagem: string, bloqueante: bool}
     */
    private function erroImportacao(string $aba, int $linha, string $coluna, string $valor, string $mensagem): array
    {
        $local = [];
        if ($aba !== '') {
            $local[] = 'aba "'.$aba.'"';
        }
        if ($linha > 0) {
            $local[] = 'linha '.$linha;
        }
        if ($coluna !== '') {
            $local[] = 'coluna '.$coluna;
        }

        $prefixo = $local !== [] ? implode(', ', $local).': ' : '';

        return [
            'aba' => $aba,
            'linha' => $linha,
            'coluna' => $coluna,
            'valor' => $valor,
            'mensagem' => $prefixo.$mensagem,
            'bloqueante' => true,
        ];
    }

    /**
     * Oferta operacional: resolve segmento/eixo e exige curso já existente no ciclo.
     *
     * @param  array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}  $resultado
     * @return array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}
     */
    private function validarOfertasEixos(array $resultado): array
    {
        $erros = $resultado['erros'];
        $cicloId = PortfolioCiclo::atual()?->id;
        $linhas = [];

        foreach ($resultado['linhas'] as $linha) {
            $numeroLinha = (int) ($linha['_linha'] ?? 0);
            $aba = (string) ($resultado['aba'] ?? '');
            $segmentoBruto = is_scalar($linha['segmento'] ?? null) ? trim((string) $linha['segmento']) : '';
            $eixoBruto = is_scalar($linha['eixo'] ?? null) ? trim((string) $linha['eixo']) : '';
            if ($segmentoBruto === '' && $eixoBruto !== '') {
                $segmentoBruto = $eixoBruto;
                $eixoBruto = '';
            }

            $resolvido = CatalogoOficial::resolverEixoESegmento(
                $eixoBruto !== '' ? $eixoBruto : null,
                $segmentoBruto !== '' ? $segmentoBruto : null,
            );

            if ($resolvido['erro'] !== null) {
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    'Segmento',
                    $segmentoBruto !== '' ? $segmentoBruto : $eixoBruto,
                    $resolvido['erro'],
                );
                $linha['status_importacao'] = 'erro';
            } else {
                $linha['eixo'] = $resolvido['eixo'];
                $linha['segmento'] = $resolvido['segmento'];
                $linha['programa'] = $resolvido['programa'] ?? null;
                $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
                $linha['eixo_id'] = $ids['eixo_id'];
                $linha['segmento_id'] = $ids['segmento_id'];
            }

            $titulo = is_scalar($linha['curso'] ?? null) ? trim((string) $linha['curso']) : '';
            $curso = $this->localizarCursoDoCiclo($titulo, $cicloId);
            if (! $curso) {
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    'Curso',
                    $titulo,
                    'Curso não encontrado no ciclo atual: "'.$titulo.'". Importe o catálogo de Cursos antes da oferta por eixo.',
                );
                $linha['status_importacao'] = 'erro';
                $linha['curso_id'] = null;
            } else {
                $linha['curso_id'] = $curso->id;
                $linha['curso'] = $curso->titulo;
            }

            unset($linha['_aba'], $linha['_linha']);
            $linhas[] = $linha;
        }

        $resultado['linhas'] = $linhas;
        $resultado['erros'] = $erros;
        $resultado['total'] = count($linhas);

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array{linhas: list<array<string, mixed>>, erros?: list<array<string, mixed>>, total?: int}  $resultado
     * @return array<string, mixed>
     */
    private function classificarAcoesUpsert(string $modulo, array $def, array $resultado): array
    {
        $modelClass = $def['model'];
        $campos = $def['db_fields'] ?? [];
        $camposUnicos = $def['unique_fields'] ?? [];
        $defaults = $def['defaults'] ?? [];
        $cicloId = in_array($modulo, ['cursos', 'plano-de-metas', 'pcas', 'eixos'], true)
            ? PortfolioCiclo::atual()?->id
            : null;

        $resumo = ['novo' => 0, 'atualizar' => 0, 'sem_alteracao' => 0, 'erro' => 0];

        foreach ($resultado['linhas'] as &$linha) {
            if (($linha['status_importacao'] ?? '') === 'erro') {
                $resumo['erro']++;
                continue;
            }

            $row = $this->montarLinhaCommit($linha, $campos, $camposUnicos, $defaults, $modulo, $cicloId);
            $existente = $this->encontrarExistente($modulo, $modelClass, $row, $cicloId);

            if (! $existente) {
                $linha['status_importacao'] = 'novo';
                $resumo['novo']++;
                continue;
            }

            if ($this->linhaSemAlteracao($existente, $row, $campos)) {
                $linha['status_importacao'] = 'sem_alteracao';
                $resumo['sem_alteracao']++;
                continue;
            }

            $linha['status_importacao'] = 'atualizar';
            $resumo['atualizar']++;
        }
        unset($linha);

        $resultado['resumo_acoes'] = $resumo;

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $linha
     * @param  list<string>  $campos
     * @param  list<string>  $camposUnicos
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function montarLinhaCommit(
        array $linha,
        array $campos,
        array $camposUnicos,
        array $defaults,
        string $modulo,
        ?int $cicloAtualId,
    ): array {
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
            if (in_array($campo, $camposUnicos, true)) {
                $valor = $this->normalizarValorUnico($valor);
            }
            if (($valor === null || $valor === '') && array_key_exists($campo, $defaults)) {
                $valor = $defaults[$campo];
            }
            $row[$campo] = $valor;
        }

        if (in_array($modulo, ['cursos', 'plano-de-metas', 'pcas', 'eixos'], true)) {
            if ($modulo === 'cursos') {
                $row = $this->normalizarCamposCurso($row);
            }
            if ($cicloAtualId && empty($row['ciclo_id'])) {
                $row['ciclo_id'] = $cicloAtualId;
            }
        }

        return $row;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $row
     */
    private function encontrarExistente(string $modulo, string $modelClass, array $row, ?int $cicloId): ?Model
    {
        $query = $modelClass::query();
        if ($cicloId && in_array($modulo, ['cursos', 'plano-de-metas', 'pcas', 'eixos'], true)) {
            $query->where('ciclo_id', $cicloId);
        }

        if ($modulo === 'cursos') {
            return $this->encontrarCursoExistente($query, $row);
        }

        if ($modulo === 'eixos') {
            return $this->encontrarOfertaExistente($query, $row);
        }

        foreach ($this->chavesUpsert($modulo) as $campo) {
            $valor = $this->normalizarValorUnico($row[$campo] ?? null);
            if ($valor === null || $valor === '') {
                continue;
            }
            $encontrado = (clone $query)->whereRaw('LOWER('.$campo.') = ?', [mb_strtolower((string) $valor)])->first();
            if ($encontrado) {
                return $encontrado;
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Curso>  $query
     * @param  array<string, mixed>  $row
     */
    private function encontrarCursoExistente($query, array $row): ?Curso
    {
        $sig = $this->normalizarValorUnico($row['codigo_sig'] ?? null);
        if ($sig) {
            $encontrado = (clone $query)->whereRaw('LOWER(codigo_sig) = ?', [mb_strtolower((string) $sig)])->first();
            if ($encontrado instanceof Curso) {
                return $encontrado;
            }
        }

        $sei = $this->normalizarValorUnico($row['processo_sei'] ?? null);
        if ($sei) {
            $encontrado = (clone $query)->whereRaw('LOWER(processo_sei) = ?', [mb_strtolower((string) $sei)])->first();
            if ($encontrado instanceof Curso) {
                return $encontrado;
            }
        }

        $titulo = mb_strtolower(trim((string) ($row['titulo'] ?? '')));
        if ($titulo === '') {
            return null;
        }

        $encontrado = (clone $query)->whereRaw('LOWER(titulo) = ?', [$titulo])->first();

        return $encontrado instanceof Curso ? $encontrado : null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<CursoPorEixo>  $query
     * @param  array<string, mixed>  $row
     */
    private function encontrarOfertaExistente($query, array $row): ?CursoPorEixo
    {
        if (! empty($row['curso_id'])) {
            $query->where('curso_id', $row['curso_id']);
        } else {
            $titulo = mb_strtolower(trim((string) ($row['curso'] ?? '')));
            if ($titulo === '') {
                return null;
            }
            $query->whereRaw('LOWER(curso) = ?', [$titulo]);
        }

        $codigo = trim((string) ($row['codigo'] ?? ''));
        if ($codigo !== '') {
            $query->where('codigo', $codigo);
        }

        $encontrado = $query->first();

        return $encontrado instanceof CursoPorEixo ? $encontrado : null;
    }

    private function localizarCursoDoCiclo(string $titulo, ?int $cicloId): ?Curso
    {
        $titulo = mb_strtolower(trim($titulo));
        if ($titulo === '') {
            return null;
        }

        $query = Curso::query()->whereRaw('LOWER(titulo) = ?', [$titulo]);
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }

        return $query->first();
    }

    /**
     * @return list<string>
     */
    private function chavesUpsert(string $modulo): array
    {
        return match ($modulo) {
            'plano-de-metas', 'pcas' => ['numero_sei', 'codigo_sig'],
            'visitas-tecnicas', 'horas-pedagogicas' => ['processo_sei'],
            'acoes-extensivas' => ['numero_processo_sei'],
            'eventos' => ['nome'],
            default => [],
        };
    }

    /**
     * @param  list<string>  $campos
     * @param  array<string, mixed>  $row
     */
    private function linhaSemAlteracao(Model $existente, array $row, array $campos): bool
    {
        foreach ($campos as $campo) {
            if (in_array($campo, ['eixo_id', 'segmento_id', 'curso_id', 'ciclo_id'], true)) {
                $atual = $existente->getAttribute($campo);
                $novo = $row[$campo] ?? null;
                if ((string) ($atual ?? '') !== (string) ($novo ?? '')) {
                    return false;
                }
                continue;
            }

            $atual = trim((string) ($existente->getAttribute($campo) ?? ''));
            $novo = trim((string) ($row[$campo] ?? ''));
            if ($atual !== $novo) {
                return false;
            }
        }

        return true;
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
