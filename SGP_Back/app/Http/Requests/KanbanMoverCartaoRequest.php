<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KanbanMoverCartaoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    public function rules(): array
    {
        return [
            'kanban_coluna_id' => [
                'required',
                'integer',
                Rule::exists('kanban_colunas', 'id'),
            ],
            'position' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'kanban_coluna_id.required' => 'Informe a coluna de destino.',
            'kanban_coluna_id.exists' => 'Coluna de destino inválida.',
            'position.required' => 'Informe a nova posição do cartão.',
            'position.min' => 'A posição deve ser zero ou maior.',
            'position.max' => 'A posição informada é inválida.',
        ];
    }
}
