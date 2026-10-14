<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Models\Curso;
use App\Models\UnidadeOferta;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CursoRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        $unidades = $this->input('unidades_oferta');

        if (is_array($unidades) && count($unidades) > 0) {
            $this->merge([
                'unidade' => $unidades[0],
            ]);
        }

        if ($this->filled('processo_sei')) {
            $this->merge([
                'processo_sei' => ProcessoSeiValido::sanitizar($this->input('processo_sei')),
            ]);
        }

        foreach (['compativel_bolsa', 'comercial', 'pcn', 'pcr'] as $campo) {
            $valor = mb_strtolower(trim((string) $this->input($campo)), 'UTF-8');
            if ($valor === 'sim') {
                $this->merge([$campo => 'SIM']);
            } elseif ($valor === 'não' || $valor === 'nao') {
                $this->merge([$campo => 'NÃO']);
            }
        }

        $this->canonicalizarEixoInput();
        $this->canonicalizarModalidadeInput();
        $this->canonicalizarSegmentoInput();
        $this->canonicalizarProgramaInput();

        if (! $this->filled('programa')) {
            $this->merge(['programa' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['required', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos'))],
            'segmento' => ['required', 'string', 'max:150', Rule::in(config('eixos_tecnologicos'))],
            'programa' => ['nullable', 'string', 'max:80', Rule::in(config('programas'))],
            'eixo_id' => ['nullable', 'integer', Rule::exists('eixos', 'id')],
            'segmento_id' => ['nullable', 'integer', Rule::exists('segmentos', 'id')],
            'modalidade' => ['required', 'string', 'max:100', Rule::in(config('cursos.modalidades'))],
            'carga_horaria' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
            'turmas' => ['required', 'string', 'max:20', 'regex:/^\d*$/'],
            'codigo_processo' => ['required', 'string', 'max:100', 'regex:/^[0-9.-]+$/'],
            'alunos' => ['required', 'string', 'max:20', 'regex:/^\d*$/'],
            'instrutor' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:5000'],
            'codigo_dn' => ['required', 'string', 'max:50', 'regex:/^[0-9.-]+$/'],
            'codigo_sig' => ['required', 'string', 'max:100', 'regex:/^[0-9.-]+$/'],
            'identificacao' => ['required', 'string', 'max:50'],
            'tipo' => ['nullable', 'string', 'max:100', Rule::in(config('cursos.tipos'))],
            'status' => ['required', 'string', 'max:50', Rule::in(config('cursos.status'))],
            'ultima_revisao' => ['required', 'string', 'regex:/^\d{1,6}$/'],
            'processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true)],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'unidade' => ['required', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'unidades_oferta' => ['required', 'array', 'min:1'],
            'unidades_oferta.*' => ['string', Rule::in(UnidadeOferta::nomesExistentes())],
            'observacoes' => ['required', 'string', 'max:2000'],
            'valores' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->valorMonetarioValido((string) $value) || $this->valorLegadoInalterado('valores', $value)) {
                        return;
                    }

                    $fail('Informe um valor monetário válido, com até duas casas decimais.');
                },
            ],
            'compativel_bolsa' => ['required', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'comercial' => ['required', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'pcn' => ['required', 'string', 'max:255', Rule::in($this->opcoesComValorLegado('pcn'))],
            'pcr' => ['required', 'string', 'max:255', Rule::in($this->opcoesComValorLegado('pcr'))],
            'justificativa_duplicidade' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unidade.required' => 'Selecione uma estrutura de oferta.',
            'unidades_oferta.required' => 'Preencha o campo Estruturas de oferta.',
            'observacoes.required' => 'Preencha o campo Observações.',
            'pcr.required' => 'Preencha o campo PCR.',
            'pcn.required' => 'Preencha o campo PCN.',
            'comercial.required' => 'Preencha o campo Comercial.',
            'compativel_bolsa.required' => 'Preencha o campo Compatível com bolsa.',
            'valores.required' => 'Preencha o campo Valores.',
            'data_fim.required' => 'Preencha o campo Data de término.',
            'data_inicio.required' => 'Preencha o campo Data de início.',
            'processo_sei.required' => 'Preencha o campo Processo SEI.',
            'ultima_revisao.required' => 'Preencha o campo Última atualização.',
            'identificacao.required' => 'Preencha o campo Identificação.',
            'codigo_dn.required' => 'Preencha o campo Cód. DN.',
            'descricao.required' => 'Preencha o campo Descrição.',
            'instrutor.required' => 'Preencha o campo Instrutor(es).',
            'alunos.required' => 'Preencha o campo Alunos (matrículas).',
            'codigo_processo.required' => 'Preencha o campo Código do processo.',
            'turmas.required' => 'Preencha o campo Quantidade de turmas.',
            'ciclo_id.required' => 'Preencha o campo Ciclo de gestão.',
            'segmento.required' => 'Preencha o campo Segmento.',
            'titulo.required' => 'O título do curso é obrigatório.',
            'eixo.required' => 'Selecione o eixo.',
            'eixo.in' => 'Selecione um eixo válido. Use somente os 5 eixos oficiais.',
            'programa.in' => 'Selecione um programa válido (60+ ou Ensino Médio).',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'carga_horaria.required' => 'A carga horária é obrigatória.',
            'modalidade.required' => 'A modalidade é obrigatória.',
            'codigo_sig.required' => 'O código SIG é obrigatório.',
            'codigo_processo.regex' => 'O código do processo deve conter somente números, ponto (.) e hífen (-).',
            'codigo_dn.regex' => 'O código DN deve conter somente números, ponto (.) e hífen (-).',
            'codigo_sig.regex' => 'O código SIG deve conter somente números, ponto (.) e hífen (-).',
            'ultima_revisao.regex' => 'Última atualização deve conter somente números e no máximo 6 algarismos.',
            'ciclo_id.exists' => 'Ciclo de gestão inválido.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'unidades_oferta.*.in' => 'Selecione unidades válidas.',
            'modalidade.in' => 'Selecione uma modalidade válida.',
            'tipo.in' => 'Selecione um tipo válido.',
            'compativel_bolsa.in' => 'Selecione SIM ou NÃO.',
            'comercial.in' => 'Selecione SIM ou NÃO.',
            'processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'carga_horaria.regex' => 'A carga horária deve conter apenas números.',
            'turmas.regex' => 'Turmas deve conter apenas números.',
            'alunos.regex' => 'Alunos deve conter apenas números.',
            'pcn.in' => 'Selecione Sim ou Não para PCN.',
            'pcr.in' => 'Selecione Sim ou Não para PCR.',
            'data_fim.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }

    private function valorMonetarioValido(string $valor): bool
    {
        return preg_match('/^(?:R\$\s*)?(?:\d+(?:[.,]\d{1,2})?|\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?)$/u', trim($valor)) === 1;
    }

    private function opcoesComValorLegado(string $campo): array
    {
        $opcoes = config('cursos.sim_nao');
        $valorLegado = $this->valorLegadoInalterado($campo, $this->input($campo));

        if ($valorLegado !== null) {
            $opcoes[] = $valorLegado;
        }

        return array_values(array_unique($opcoes));
    }

    private function valorLegadoInalterado(string $campo, mixed $valor): ?string
    {
        $curso = $this->route('curso');
        if (! $curso instanceof Curso || ! is_string($valor)) {
            return null;
        }

        $original = $curso->getAttribute($campo);

        return is_string($original) && $valor === $original ? $original : null;
    }
}
