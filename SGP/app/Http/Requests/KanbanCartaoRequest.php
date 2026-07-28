<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KanbanCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        $rules = [
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
        ];

        if ($this->isMethod('post')) {
            $rules['coluna_titulo'] = ['required', 'string', 'max:80'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título do cartão.',
            'titulo.max' => 'O título pode ter no máximo 150 caracteres.',
            'coluna_titulo.required' => 'Informe o nome da coluna.',
            'coluna_titulo.max' => 'O nome da coluna pode ter no máximo 80 caracteres.',
        ];
    }
}
