<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\RegiaoAdministrativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegiaoAdministrativaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->has('ativo')) {
            $this->merge([
                'ativo' => filter_var($this->input('ativo'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->filled('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }
    }

    public function rules(): array
    {
        $regiao = $this->route('regiao_administrativa') ?? $this->route('regiaoAdministrativa');
        $regiaoId = $regiao instanceof RegiaoAdministrativa ? $regiao->id : $regiao;

        return [
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('regioes_administrativas', 'nome')->ignore($regiaoId),
            ],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da região administrativa.',
            'nome.unique' => 'Já existe uma região administrativa com este nome.',
        ];
    }
}
