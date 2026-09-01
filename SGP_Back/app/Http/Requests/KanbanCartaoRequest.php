<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;

class KanbanCartaoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('titulo')) {
            $merge['titulo'] = trim((string) $this->input('titulo'));
        }

        if ($this->filled('coluna_titulo')) {
            $merge['coluna_titulo'] = trim((string) $this->input('coluna_titulo'));
        }

        if ($this->exists('descricao') && $this->input('descricao') === '') {
            $merge['descricao'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $rules = [
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:2000'],
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
            'descricao.max' => 'A descrição pode ter no máximo 2000 caracteres.',
            'coluna_titulo.required' => 'Informe o nome da coluna.',
            'coluna_titulo.max' => 'O nome da coluna pode ter no máximo 80 caracteres.',
        ];
    }
}
