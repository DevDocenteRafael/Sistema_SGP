<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitaTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        return [
            'unidade' => ['required', 'string', 'max:100', Rule::in(config('unidades'))],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('visitas_tecnicas.eixos'))],
            'processo_sei' => ['required', 'string', 'max:100'],
            'data_solicitacao' => ['required', 'date'],
            'data_visita_prevista' => ['required', 'date'],
            'prazo_limite' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('visitas_tecnicas.status'))],
            'responsavel' => ['required', 'string', 'max:150'],
            'relatorio' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
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
            'data_solicitacao.required' => 'A data de solicitação é obrigatória.',
            'data_visita_prevista.required' => 'A data prevista da visita é obrigatória.',
            'prazo_limite.required' => 'O prazo limite é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'responsavel.required' => 'O responsável é obrigatório.',
        ];
    }
}
