<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TermoReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos', []))],
            'processo_sei' => ['required', 'string', 'max:50'],
            'prazo_deadline' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('termos_referencia.status', ['Planejamento', 'Em Andamento', 'Concluído', 'Arquivado']))],
            'observacao' => ['nullable', 'string'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'concluido_em' => ['nullable', 'datetime'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do Termo de Referência é obrigatório.',
            'nome.max' => 'O nome não pode exceder 255 caracteres.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'prazo_deadline.required' => 'O prazo/deadline é obrigatório.',
            'prazo_deadline.date' => 'O prazo deve ser uma data válida.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'data_fim.after_or_equal' => 'A data de término deve ser posterior ou igual à data de início.',
        ];
    }
}
