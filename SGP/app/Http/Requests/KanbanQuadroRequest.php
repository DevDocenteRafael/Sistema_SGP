<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KanbanQuadroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
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
