<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;

class KanbanColunaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->filled('titulo')) {
            $this->merge([
                'titulo' => trim((string) $this->input('titulo')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o nome da coluna.',
            'titulo.max' => 'O nome da coluna pode ter no máximo 80 caracteres.',
        ];
    }
}
