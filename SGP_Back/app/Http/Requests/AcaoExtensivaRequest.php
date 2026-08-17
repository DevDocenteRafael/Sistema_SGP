<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcaoExtensivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        return [
            'priorizacao' => ['required', 'string', 'max:20', Rule::in(config('acoes_extensivas.priorizacoes'))],
            'atribuido' => ['required', 'string', 'max:100'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('acoes_extensivas.eixos'))],
            'numero_processo_sei' => ['required', 'string', 'max:50'],
            'tipo' => ['required', 'string', 'max:100', Rule::in(config('acoes_extensivas.tipos'))],
            'assunto' => ['required', 'string', 'max:500'],
            'objetivo' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('acoes_extensivas.status'))],
            'ultima_atualizacao' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'priorizacao.required' => 'A priorização é obrigatória.',
            'priorizacao.in' => 'Priorização inválida.',
            'atribuido.required' => 'Informe o responsável atribuído.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Eixo inválido.',
            'numero_processo_sei.required' => 'O número do processo SEI é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
            'tipo.in' => 'Tipo inválido.',
            'assunto.required' => 'O assunto é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ];
    }
}
