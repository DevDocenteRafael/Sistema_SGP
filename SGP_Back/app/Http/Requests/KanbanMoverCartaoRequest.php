<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KanbanMoverCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        return [
            'kanban_coluna_id' => [
                'required',
                'integer',
                Rule::exists('kanban_colunas', 'id'),
            ],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'kanban_coluna_id.required' => 'Informe a coluna de destino.',
            'kanban_coluna_id.exists' => 'Coluna de destino inválida.',
            'position.required' => 'Informe a nova posição do cartão.',
            'position.min' => 'A posição deve ser zero ou maior.',
        ];
    }
}
