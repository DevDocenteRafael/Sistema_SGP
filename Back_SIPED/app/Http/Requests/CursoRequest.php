<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\UnidadeOferta;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CursoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

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
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos'))],
            'modalidade' => ['nullable', 'string', 'max:100', Rule::in(config('cursos.modalidades'))],
            'carga_horaria' => ['nullable', 'string', 'max:50', 'regex:/^\d+$/'],
            'turmas' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'codigo_processo' => ['nullable', 'string', 'max:100'],
            'alunos' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'codigo_dn' => ['nullable', 'string', 'max:50'],
            'codigo_sig' => ['nullable', 'string', 'max:100'],
            'identificacao' => ['nullable', 'string', 'max:50'],
            'tipo' => ['nullable', 'string', 'max:100', Rule::in(config('cursos.tipos'))],
            'status' => ['required', 'string', 'max:50', Rule::in(config('cursos.status'))],
            'ultima_revisao' => ['nullable', 'string', 'max:50'],
            'processo_sei' => ['nullable', 'string', 'max:100', new ProcessoSeiValido],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'unidade' => ['nullable', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'unidades_oferta' => ['nullable', 'array'],
            'unidades_oferta.*' => ['string', Rule::in(UnidadeOferta::nomesExistentes())],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'valores' => ['nullable', 'string', 'max:255'],
            'compativel_bolsa' => ['nullable', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'comercial' => ['nullable', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'pcn' => ['nullable', 'string', 'max:255'],
            'pcr' => ['nullable', 'string', 'max:255'],
            'justificativa_duplicidade' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título do curso é obrigatório.',
            'eixo.required' => 'Selecione o segmento / área.',
            'eixo.in' => 'Selecione um segmento válido.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'ciclo_id.exists' => 'Ciclo de portfólio inválido.',
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
            'data_fim.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
