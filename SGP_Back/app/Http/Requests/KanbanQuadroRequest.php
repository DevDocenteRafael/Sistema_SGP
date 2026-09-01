<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;

class KanbanQuadroRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->filled('nome')) {
            $this->merge([
                'nome' => trim((string) $this->input('nome')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do quadro.',
            'nome.max' => 'O nome do quadro pode ter no máximo 100 caracteres.',
        ];
    }
}
