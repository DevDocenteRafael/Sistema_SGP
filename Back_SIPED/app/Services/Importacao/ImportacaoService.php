<?php

namespace App\Services\Importacao;

use App\Exceptions\ImportacaoInvalidaException;
use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Services\CadastroAuditoriaService;
use App\Services\CicloContextoService;
use App\Support\CatalogoInstitucional;
use App\Support\CatalogoOficial;
use App\Support\ConciliadorCursoOferta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;
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

        $resultado['colunas_preview'] = array_merge(
            $def['preview_columns'] ?? [],
            [['key' => 'pendencias', 'label' => 'Campos faltantes']],
        );
        $resultado['label'] = $def['label'];

        if (($def['key'] ?? $modulo) === 'cursos') {
            $resultado = $this->validarCatalogoCursos($resultado);
        } elseif (($def['key'] ?? $modulo) === 'eixos') {
            $resultado = $this->validarOfertasEixos($resultado);
        } else {
            $resultado = $this->canonicalizarEixosNasLinhas($resultado);
            if ($modulo === 'plano-de-metas') {
                $resultado = $this->canonicalizarAreasPlanejamento($resultado);
            }
            if ($modulo === 'acoes-extensivas') {
                $resultado = $this->separarPrioridadeSetorStatus($resultado);
            }
        }

        $resultado = $this->avaliarCompletude($modulo, $def, $resultado);
        $resultado = $this->classificarAcoesUpsert($modulo, $def, $resultado);
        if ($this->moduloTemCiclo($modulo)) {
            $resultado['ciclo'] = app(CicloContextoService::class)->meta();
        }

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

        $linhasValidas = collect($resultado['linhas'])->where('status_importacao', '!=', 'erro')->count();
        if ($resultado['total'] === 0 || $linhasValidas === 0) {
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
        $resumo = ['novo' => 0, 'atualizar' => 0, 'sem_alteracao' => 0, 'incompleto' => 0, 'falha' => 0];
        $incompletos = [];

        $cicloAtualId = $this->cicloDestinoId($modulo);

        DB::transaction(function () use ($modelClass, $campos, &$resultado, $modulo, $camposUnicos, $defaults, $usuarioId, $def, $cicloAtualId, &$backup, &$resumo, &$incompletos) {
            $backup = $this->backupService->backupAntesDeSubstituir($modulo, $modelClass);

            foreach ($resultado['linhas'] as $linha) {
                if (($linha['status_importacao'] ?? '') === 'erro') {
                    continue;
                }

                try {
                    // Savepoint por linha: uma linha com falha não derruba o lote.
                    $registro = DB::transaction(function () use ($linha, $campos, $camposUnicos, $defaults, $modulo, $cicloAtualId, $modelClass, $usuarioId, &$resumo) {
                        $row = $this->montarLinhaCommit($linha, $campos, $camposUnicos, $defaults, $modulo, $cicloAtualId);
                        $existente = $this->encontrarExistente($modulo, $modelClass, $row, $cicloAtualId);

                        if (! $existente) {
                            if ($usuarioId) {
                                $row['criado_por'] = $usuarioId;
                                $row['atualizado_por'] = $usuarioId;
                            }
                            $criado = $modelClass::query()->create($row);
                            $resumo['novo']++;

                            return $criado;
                        }

                        if (($linha['status_importacao'] ?? '') === 'sem_alteracao') {
                            $resumo['sem_alteracao']++;

                            return $existente;
                        }

                        unset($row['ciclo_id']);
                        if ($usuarioId) {
                            $row['atualizado_por'] = $usuarioId;
                        }
                        $existente->fill($row);
                        $existente->save();
                        $resumo['atualizar']++;

                        return $existente;
                    });
                } catch (Throwable $e) {
                    Log::warning('Importação: linha não gravada', ['modulo' => $modulo, 'erro' => $e->getMessage()]);
                    $resumo['falha']++;
                    $resultado['erros'][] = $this->erroImportacao(
                        '',
                        (int) ($linha['linha_planilha'] ?? 0),
                        '',
                        $this->rotuloLinha($linha),
                        'Não foi possível gravar esta linha; ela foi ignorada e as demais seguiram. Detalhe: '.mb_substr($e->getMessage(), 0, 200),
                        false,
                    );

                    continue;
                }

                if (! empty($linha['campos_faltantes'])) {
                    $resumo['incompleto']++;
                    if (count($incompletos) < self::LIMITE_INCOMPLETOS_HISTORICO) {
                        $incompletos[] = [
                            'id' => $registro->getKey(),
                            'rotulo' => $this->rotuloLinha($linha),
                            'linha' => $linha['linha_planilha'] ?? null,
                            'campos' => $linha['campos_faltantes'],
                        ];
                    }
                }
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
                    'incompleto' => $resumo['incompleto'],
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
        $resultado['incompletos_registros'] = $incompletos;

        return $resultado;
    }

    /**
     * Ações Extensivas (SPEC 06): prioridade, setor/etapa e status separados.
     * - "Resolvido" na prioridade vira situação legada;
     * - a coluna "Status" da planilha traz o setor (CPED/DEP/DIREG/NC); outro valor
     *   é preservado como status de execução, com aviso.
     *
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function separarPrioridadeSetorStatus(array $resultado): array
    {
        $prioridades = config('acoes_extensivas.priorizacoes', []);
        $setores = config('acoes_extensivas.setores', []);

        foreach ($resultado['linhas'] as &$linha) {
            $numero = (int) ($linha['linha_planilha'] ?? 0);

            $prioridade = trim((string) ($linha['priorizacao'] ?? ''));
            if ($prioridade !== '') {
                $oficial = collect($prioridades)->first(fn ($p) => CatalogoOficial::chave($p) === CatalogoOficial::chave($prioridade));
                if ($oficial) {
                    $linha['priorizacao'] = $oficial;
                } else {
                    if (CatalogoOficial::chave($prioridade) === 'resolvido') {
                        $linha['situacao_legada'] = 'Resolvido';
                    }
                    $resultado['erros'][] = $this->erroImportacao('', $numero, 'Priorização', $prioridade,
                        'Prioridade "'.$prioridade.'" não é Baixa, Média ou Alta. Importado sem prioridade'
                        .(isset($linha['situacao_legada']) ? ' (guardado como situação legada "Resolvido")' : '').'.', false);
                    $linha['priorizacao'] = null;
                }
            }

            $setor = trim((string) ($linha['setor_atual'] ?? ''));
            if ($setor !== '') {
                $oficial = collect($setores)->first(fn ($s) => mb_strtoupper($s) === mb_strtoupper($setor));
                if ($oficial) {
                    $linha['setor_atual'] = $oficial;
                } else {
                    $linha['status'] = $setor;
                    $linha['setor_atual'] = null;
                    $resultado['erros'][] = $this->erroImportacao('', $numero, 'Setor/etapa', $setor,
                        '"'.$setor.'" não é um setor/etapa (CPED, DEP, DIREG, NC). Guardado como status e importado sem setor.', false);
                }
            }
        }
        unset($linha);

        return $resultado;
    }

    /**
     * Área do planejamento: grafias conhecidas viram a área oficial; valor não reconhecido
     * é importado sem área, com aviso (valor original preservado na mensagem).
     *
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function canonicalizarAreasPlanejamento(array $resultado): array
    {
        foreach ($resultado['linhas'] as &$linha) {
            $bruto = $linha['area_planejamento'] ?? null;
            if ($this->valorVazio($bruto)) {
                continue;
            }

            $area = \App\Support\AreaPlanejamento::canonicalizar($bruto);
            if ($area === null) {
                $resultado['erros'][] = $this->erroImportacao(
                    '',
                    (int) ($linha['linha_planilha'] ?? 0),
                    'Área',
                    (string) $bruto,
                    'Área do planejamento não reconhecida: "'.$bruto.'". Use Planejamento Estratégico, DN, DEF ou CPED. Importado sem área.',
                    false,
                );
            }
            $linha['area_planejamento'] = $area;
        }
        unset($linha);

        return $resultado;
    }

    /**
     * Códigos oficiais do curso que mudariam com a importação.
     *
     * @param  array<string, mixed>  $row
     * @return list<array{rotulo: string, cadastro: string, planilha: string}>
     */
    private function divergenciasDeCodigo(Model $existente, array $row): array
    {
        $campos = ['codigo_sig' => 'Cód. SIG', 'processo_sei' => 'Processo SEI', 'codigo_dn' => 'Cód. DN'];
        $divergencias = [];

        foreach ($campos as $campo => $rotulo) {
            $cadastro = trim((string) ($existente->getAttribute($campo) ?? ''));
            $planilha = trim((string) ($row[$campo] ?? ''));
            if ($cadastro !== '' && $planilha !== '' && mb_strtolower($cadastro) !== mb_strtolower($planilha)) {
                $divergencias[] = ['rotulo' => $rotulo, 'cadastro' => $cadastro, 'planilha' => $planilha];
            }
        }

        return $divergencias;
    }

    /** Quantos registros incompletos o histórico guarda com link para correção. */
    private const LIMITE_INCOMPLETOS_HISTORICO = 1000;

    /**
     * Campos sem os quais a linha não vira registro (identidade mínima / NOT NULL no banco).
     *
     * @return list<string>
     */
    private function camposIdentidade(string $modulo): array
    {
        return match ($modulo) {
            'cursos' => ['titulo'],
            'eixos' => ['curso', 'eixo'],
            default => [],
        };
    }

    /**
     * Campos que o formulário do módulo exige. Se vierem vazios na planilha, o registro
     * é importado mesmo assim e marcado como incompleto para correção no cadastro.
     *
     * @param  array<string, mixed>  $def
     * @return array<string, string>  campo => rótulo
     */
    private function camposEsperados(array $def): array
    {
        $requestClass = $def['request'] ?? null;
        if (! is_string($requestClass) || ! class_exists($requestClass)) {
            return [];
        }

        try {
            $request = new $requestClass;
            $regras = $request->rules();
            $mensagens = method_exists($request, 'messages') ? $request->messages() : [];
        } catch (Throwable) {
            return [];
        }

        $rotulos = collect($def['preview_columns'] ?? [])->pluck('label', 'key')->all();
        $internos = ['eixo_id', 'segmento_id', 'curso_id', 'ciclo_id', 'programa'];
        $esperados = [];

        foreach ($def['db_fields'] ?? [] as $campo) {
            if (in_array($campo, $internos, true) || ! isset($regras[$campo])) {
                continue;
            }
            $lista = is_string($regras[$campo]) ? explode('|', $regras[$campo]) : (array) $regras[$campo];
            if (in_array('required', $lista, true)) {
                $esperados[$campo] = $rotulos[$campo]
                    ?? $this->rotuloDaMensagem($mensagens[$campo.'.required'] ?? null)
                    ?? $this->rotuloCampo($campo);
            }
        }

        return $esperados;
    }

    /**
     * Extrai o nome do campo da mensagem do formulário
     * (ex.: "Preencha o campo Identificação." => "Identificação").
     */
    private function rotuloDaMensagem(?string $mensagem): ?string
    {
        if (! $mensagem) {
            return null;
        }

        $padroes = [
            '/^Preencha o campo (.+?)\.?$/u',
            '/^Informe (?:o|a|os|as) (.+?)\.?$/u',
            '/^Selecione (?:o|a|um|uma) (.+?)\.?$/u',
            '/^(?:O|A|Os|As) (.+?) (?:é|são) obrigatóri[oa]s?\.?$/u',
        ];
        foreach ($padroes as $padrao) {
            if (preg_match($padrao, trim($mensagem), $m)) {
                $rotulo = trim($m[1]);

                return mb_strtoupper(mb_substr($rotulo, 0, 1)).mb_substr($rotulo, 1);
            }
        }

        return null;
    }

    private function rotuloCampo(string $campo): string
    {
        $mapa = [
            'processo_sei' => 'Processo SEI',
            'numero_sei' => 'Número SEI',
            'numero_processo_sei' => 'Processo SEI',
            'codigo_sig' => 'Código SIG',
            'codigo_dn' => 'Código DN',
            'ch' => 'CH',
            'carga_horaria' => 'Carga horária',
            'pcn' => 'PCN',
            'pcr' => 'PCR',
            'mes_entrega' => 'Mês de entrega',
            'ultima_revisao' => 'Última revisão',
            'ultima_atualizacao' => 'Última atualização',
            'observacao' => 'Observação',
            'observacoes' => 'Observações',
            'titulo' => 'Título',
            'identificacao' => 'Identificação',
            'compativel_bolsa' => 'Compatível com bolsa',
            'numero_processo_sei' => 'Processo SEI',
            'quantidade_pessoas' => 'Quantidade de pessoas',
            'possui_acao_extensiva' => 'Possui ação extensiva',
            'data_solicitacao' => 'Data de solicitação',
            'responsavel' => 'Responsável',
            'relatorio' => 'Relatório',
            'matricula' => 'Matrícula',
            'priorizacao' => 'Priorização',
            'atribuido' => 'Atribuído',
            'precificacao' => 'Precificação',
            'codigo' => 'Código',
        ];

        return $mapa[$campo] ?? mb_convert_case(str_replace('_', ' ', $campo), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $linha
     */
    private function rotuloLinha(array $linha): string
    {
        foreach (['titulo', 'curso', 'nome', 'assunto', 'pessoa', 'numero_sei', 'processo_sei', 'numero_processo_sei', 'codigo'] as $campo) {
            $valor = $linha[$campo] ?? null;
            if (is_scalar($valor) && trim((string) $valor) !== '') {
                return mb_substr(trim((string) $valor), 0, 255);
            }
        }

        return 'Linha '.($linha['linha_planilha'] ?? '?');
    }

    /**
     * Importação parcial: linha sem identidade mínima é ignorada (com motivo);
     * linha com campos complementares vazios é importada e marcada como incompleta.
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function avaliarCompletude(string $modulo, array $def, array $resultado): array
    {
        $identidade = $this->camposIdentidade($modulo);
        $esperados = $this->camposEsperados($def);
        $incompletos = 0;

        foreach ($resultado['linhas'] as &$linha) {
            if (($linha['status_importacao'] ?? '') === 'erro') {
                continue;
            }

            $semIdentidade = array_values(array_filter(
                $identidade,
                fn (string $campo) => $this->valorVazio($linha[$campo] ?? null),
            ));
            if ($semIdentidade !== []) {
                $linha['status_importacao'] = 'erro';
                $resultado['erros'][] = $this->erroImportacao(
                    '',
                    (int) ($linha['linha_planilha'] ?? 0),
                    implode(', ', array_map(fn ($c) => $esperados[$c] ?? $this->rotuloCampo($c), $semIdentidade)),
                    '',
                    'Linha sem identidade mínima ('.implode(', ', array_map(fn ($c) => $esperados[$c] ?? $this->rotuloCampo($c), $semIdentidade)).'). Somente esta linha foi ignorada.',
                    false,
                );

                continue;
            }

            $faltantes = [];
            foreach ($esperados as $campo => $rotulo) {
                if ($this->valorVazio($linha[$campo] ?? null)) {
                    $faltantes[] = $rotulo;
                }
            }

            $linha['campos_faltantes'] = $faltantes;
            $linha['pendencias'] = $faltantes === [] ? null : implode(', ', $faltantes);
            if ($faltantes !== []) {
                $incompletos++;
            }
        }
        unset($linha);

        $resultado['incompletos'] = $incompletos;
        $resultado['campos_esperados'] = array_values($esperados);

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
            if ($valor === '') {
                $registro[$campo] = null;
                continue;
            }

            if (mb_strlen($valor) > 80) {
                // Texto longo (observação) que caiu na coluna de código: não é código oficial.
                $registro['_codigos_descartados'][$campo] = $valor;
                $registro[$campo] = null;
                continue;
            }

            // Código oficial: mantido exatamente (sem truncar).
            $registro[$campo] = $valor;
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
                $erros[] = [
                    'linha' => $row,
                    'mensagem' => 'Linha '.$row.': sem identidade mínima. Somente esta linha foi ignorada.',
                    'bloqueante' => false,
                ];
                $ignoradas++;
                continue;
            }

            $registro['_linha'] = $row;
            $registro['linha_planilha'] = $row;
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
                    'mensagem' => 'Aba "'.$abaLabel.'", linha '.$row.': sem identidade mínima para '.$def['label']
                        .' ('.implode(' ou ', array_map(fn ($c) => $this->rotuloCampo($c), $def['required_any'] ?? [])).'). Somente esta linha foi ignorada.',
                    'bloqueante' => false,
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

            $registro['linha_planilha'] = $row;
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
            if (in_array($campo, self::CAMPOS_CODIGO, true)) {
                $registro[$campo] = $this->valorCodigo($sheet, $col, $row);
                continue;
            }

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
     * Códigos oficiais (SIG, DN, SEI, matrícula...): preservados exatamente como aparecem na planilha.
     */
    private const CAMPOS_CODIGO = [
        'codigo_sig', 'codigo_dn', 'processo_sei', 'numero_sei', 'numero_processo_sei', 'codigo', 'matricula',
    ];

    /**
     * Lê um código como texto. Se o Excel guardou como número, usa o valor exibido
     * na célula (mantém zeros à esquerda de formatos como "000123") e nunca a
     * notação científica (1,23E+16).
     */
    private function valorCodigo(Worksheet $sheet, int $col, int $row): ?string
    {
        $celula = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$row);
        $bruto = $celula->getCalculatedValue();

        if (is_int($bruto) || is_float($bruto)) {
            $exibido = trim((string) $celula->getFormattedValue());
            $inteiroComDecimais = is_float($bruto) && floor($bruto) === $bruto && preg_match('/[.,]0+$/', $exibido);
            if ($exibido !== '' && ! $inteiroComDecimais && ! preg_match('/e[+-]?\d+$/i', $exibido) && preg_match('/^[\d.\/\-\s]+$/', $exibido)) {
                return $exibido;
            }

            if (is_float($bruto) && floor($bruto) === $bruto) {
                return sprintf('%.0f', $bruto);
            }

            return $this->textoOuNulo($bruto);
        }

        return $this->textoOuNulo($bruto);
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
                // Sem eixo/segmento oficial não é seguro criar o curso: ignora só a linha.
                $valor = $resolvido['segmento'] ?? $segmentoBruto ?: $eixoBruto;
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    str_contains((string) $resolvido['erro'], 'Segmento') ? 'Segmento' : 'Eixo',
                    (string) $valor,
                    $resolvido['erro'].' Somente esta linha foi ignorada (valor original: "'.$valor.'").',
                    false,
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
                // Modalidade é complementar: importa sem ela e sinaliza para correção.
                $valorModalidade = is_scalar($linha['modalidade'] ?? null)
                    ? (string) ($linha['modalidade'] ?? '')
                    : '';
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    'Modalidade',
                    $valorModalidade !== '' ? $valorModalidade : (string) ($linha['tipo'] ?? ''),
                    $resolucao['erro'].' O curso será importado sem modalidade; corrija no cadastro.',
                    false,
                );
                $linha['modalidade'] = null;
            }

            foreach ($linha['_codigos_descartados'] ?? [] as $campo => $valorDescartado) {
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    $campo === 'codigo_sig' ? 'Cód. SIG' : 'Cód. DN',
                    mb_substr((string) $valorDescartado, 0, 120),
                    'O conteúdo não parece um código oficial (texto longo); o curso foi importado sem esse código.',
                    false,
                );
            }

            $linha['linha_planilha'] = $numeroLinha ?: ($linha['linha_planilha'] ?? null);
            unset($linha['_aba'], $linha['_linha'], $linha['_codigos_descartados']);
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
    private function erroImportacao(string $aba, int $linha, string $coluna, string $valor, string $mensagem, bool $bloqueante = true): array
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
            'bloqueante' => $bloqueante,
        ];
    }

    /**
     * Oferta operacional: resolve segmento/eixo e tenta vincular a um curso do catálogo.
     *
     * @param  array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}  $resultado
     * @return array{linhas: list<array<string, mixed>>, erros: list<array<string, mixed>>, total: int, ignoradas: int, aba: string}
     */
    private function validarOfertasEixos(array $resultado): array
    {
        $erros = $resultado['erros'];
        $cicloId = $this->cicloDestinoId('eixos');
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
                    $resolvido['erro'].' Somente esta linha foi ignorada.',
                    false,
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
            $curso = ConciliadorCursoOferta::localizar([
                'titulo' => $titulo,
                'eixo' => $resolvido['eixo'] ?? $eixoBruto,
                'segmento' => $resolvido['segmento'] ?? $segmentoBruto,
                'ch' => is_scalar($linha['ch'] ?? null) ? (string) $linha['ch'] : null,
                'codigo' => is_scalar($linha['codigo'] ?? null) ? (string) $linha['codigo'] : null,
            ], $cicloId);
            if (! $curso) {
                $erros[] = $this->erroImportacao(
                    $aba,
                    $numeroLinha,
                    'Curso',
                    $titulo,
                    'Sem correspondência no catálogo de Cursos: "'.$titulo.'". A oferta será importada como pendência e nenhum curso novo será criado.',
                    false,
                );
                if (($linha['status_importacao'] ?? '') !== 'erro') {
                    $linha['status_importacao'] = 'pendente';
                }
                $linha['curso_id'] = null;
            } else {
                $linha['curso_id'] = $curso->id;
                $linha['curso'] = $curso->titulo;
            }

            $linha['linha_planilha'] = $numeroLinha ?: ($linha['linha_planilha'] ?? null);
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
        $cicloId = $this->cicloDestinoId($modulo);

        $resumo = ['novo' => 0, 'atualizar' => 0, 'sem_alteracao' => 0, 'erro' => 0, 'pendente' => 0, 'incompleto' => 0];

        foreach ($resultado['linhas'] as &$linha) {
            if (($linha['status_importacao'] ?? '') === 'erro') {
                $resumo['erro']++;
                continue;
            }

            if (! empty($linha['campos_faltantes'])) {
                $resumo['incompleto']++;
            }

            $eraPendente = ($linha['status_importacao'] ?? '') === 'pendente';

            $row = $this->montarLinhaCommit($linha, $campos, $camposUnicos, $defaults, $modulo, $cicloId);
            $existente = $this->encontrarExistente($modulo, $modelClass, $row, $cicloId);

            // Registro correspondente está na lixeira: não recria nem sobrescreve.
            if (! $existente && $this->encontrarExistente($modulo, $modelClass, $row, $cicloId, true)) {
                $linha['status_importacao'] = 'erro';
                $resumo['erro']++;
                if (! empty($linha['campos_faltantes'])) {
                    $resumo['incompleto']--;
                }
                $resultado['erros'][] = $this->erroImportacao(
                    '',
                    (int) ($linha['linha_planilha'] ?? 0),
                    '',
                    $this->rotuloLinha($linha),
                    'O registro correspondente foi excluído. Restaure-o na Auditoria antes de reimportar; somente esta linha foi ignorada.',
                    false,
                );

                continue;
            }

            if ($existente && $modulo === 'cursos') {
                foreach ($this->divergenciasDeCodigo($existente, $row) as $divergencia) {
                    $resultado['erros'][] = $this->erroImportacao(
                        '',
                        (int) ($linha['linha_planilha'] ?? 0),
                        $divergencia['rotulo'],
                        $divergencia['planilha'],
                        'Código diferente do cadastro de "'.$this->rotuloLinha($linha).'": cadastro "'.$divergencia['cadastro'].'", planilha "'.$divergencia['planilha'].'". Será gravado o valor da planilha (fonte oficial).',
                        false,
                    );
                    $linha['divergencias'][] = $divergencia['rotulo'];
                }
            }

            if (! $existente) {
                $resumo['novo']++;
                $linha['status_importacao'] = $eraPendente ? 'pendente' : 'novo';
            } elseif ($this->linhaSemAlteracao($existente, $row, $campos)) {
                $resumo['sem_alteracao']++;
                $linha['status_importacao'] = $eraPendente ? 'pendente' : 'sem_alteracao';
            } else {
                $resumo['atualizar']++;
                $linha['status_importacao'] = $eraPendente ? 'pendente' : 'atualizar';
            }

            if ($eraPendente) {
                $resumo['pendente']++;
            }
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

        if ($this->moduloTemCiclo($modulo)) {
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
    private function encontrarExistente(string $modulo, string $modelClass, array $row, ?int $cicloId, bool $naLixeira = false): ?Model
    {
        $usaLixeira = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true);
        if ($naLixeira && ! $usaLixeira) {
            return null;
        }
        $query = $naLixeira ? $modelClass::query()->onlyTrashed() : $modelClass::query();
        if ($cicloId && $this->moduloTemCiclo($modulo)) {
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
     * @return list<string>
     */
    private function modulosComCiclo(): array
    {
        return [
            'cursos', 'plano-de-metas', 'pcas', 'eixos',
            'visitas-tecnicas', 'horas-pedagogicas', 'acoes-extensivas', 'eventos',
        ];
    }

    private function moduloTemCiclo(string $modulo): bool
    {
        return in_array($modulo, $this->modulosComCiclo(), true);
    }

    private function cicloDestinoId(string $modulo): ?int
    {
        if (! $this->moduloTemCiclo($modulo)) {
            return null;
        }

        $ciclo = app(CicloContextoService::class)->resolver();
        if (! $ciclo) {
            throw new InvalidArgumentException('Informe o ciclo de gestão de destino da importação.');
        }

        return $ciclo->id;
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
