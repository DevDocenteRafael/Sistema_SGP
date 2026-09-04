<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\UnidadeOferta;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitaTecnicaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->filled('processo_sei')) {
            $this->merge([
                'processo_sei' => ProcessoSeiValido::sanitizar($this->input('processo_sei')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'unidade' => ['required', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('visitas_tecnicas.eixos'))],
            'processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true)],
            'data_solicitacao' => ['required', 'date'],
            'data_visita_prevista' => ['required', 'date', 'after_or_equal:data_solicitacao'],
            'prazo_limite' => ['required', 'date', 'after_or_equal:data_solicitacao'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('visitas_tecnicas.status'))],
            'responsavel' => ['required', 'string', 'max:150'],
            'relatorio' => ['nullable', 'string', 'max:2000'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unidade.required' => 'A unidade é obrigatória.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'data_solicitacao.required' => 'A data de solicitação é obrigatória.',
            'data_visita_prevista.required' => 'A data prevista da visita é obrigatória.',
            'data_visita_prevista.after_or_equal' => 'A data prevista da visita deve ser igual ou posterior à data de solicitação.',
            'prazo_limite.required' => 'O prazo limite é obrigatório.',
            'prazo_limite.after_or_equal' => 'O prazo limite deve ser igual ou posterior à data de solicitação.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'responsavel.required' => 'O responsável é obrigatório.',
        ];
    }
}
