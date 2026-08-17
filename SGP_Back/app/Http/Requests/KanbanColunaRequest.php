<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KanbanColunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
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
