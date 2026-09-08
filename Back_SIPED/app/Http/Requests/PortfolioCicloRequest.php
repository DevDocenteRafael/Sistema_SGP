<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioCicloRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->input('observacao') === '') {
            $this->merge(['observacao' => null]);
        }
    }

    public function rules(): array
    {
        $ciclo = $this->route('portfolioCiclo');

        return [
            'nome' => [
                'required',
                'string',
                'max:80',
                Rule::unique('portfolio_ciclos', 'nome')->ignore($ciclo?->id),
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'atual' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do ciclo de portfólio.',
            'nome.max' => 'O nome deve ter no máximo 80 caracteres.',
            'nome.unique' => 'Já existe um ciclo com este nome.',
            'observacao.max' => 'A observação deve ter no máximo 2000 caracteres.',
        ];
    }
}
