<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CpedEquipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        $tiposComEixo = ['responsavel', 'instrutor'];

        return [
            'nome' => ['required', 'string', 'max:100'],
            'cargo' => ['required', 'string', 'max:100'],
            'setor' => ['required', 'string', 'max:100', Rule::in(config('cped_equipes.setores'))],
            'contato' => ['required', 'email', 'max:100'],
            'tipo' => ['required', 'string', 'max:50', Rule::in(config('cped_equipes.tipos'))],
            'eixo_vinculado' => [
                Rule::requiredIf(fn () => in_array($this->input('tipo'), $tiposComEixo, true)),
                'nullable',
                'string',
                'max:100',
                Rule::in(config('cped_equipes.eixos')),
            ],
            'iniciais' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'string'],
            'cor' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome completo.',
            'cargo.required' => 'Informe o cargo / função.',
            'setor.required' => 'Selecione o setor / eixo.',
            'setor.in' => 'Setor / eixo inválido.',
            'contato.required' => 'Informe o e-mail de contato.',
            'contato.email' => 'Informe um e-mail válido.',
            'tipo.required' => 'Selecione o tipo do membro.',
            'tipo.in' => 'Tipo inválido.',
            'eixo_vinculado.required' => 'Informe o eixo vinculado para este tipo.',
            'eixo_vinculado.in' => 'Eixo vinculado inválido.',
        ];
    }
}
