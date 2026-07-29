<?php

namespace App\Http\Requests;

use App\Models\Fluxograma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FluxogramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        $tituloRule = $this->isMethod('POST')
            ? ['required', 'string', 'max:100']
            : ['sometimes', 'required', 'string', 'max:100'];

        return [
            'titulo' => $tituloRule,
            'descricao' => ['nullable', 'string', 'max:2000'],
            'tipo' => ['sometimes', 'string', Rule::in([Fluxograma::TIPO_LINEAR, Fluxograma::TIPO_FUNCIONAL])],
            'diagrama' => ['sometimes', 'array'],
            'diagrama.nodes' => ['sometimes', 'array'],
            'diagrama.edges' => ['sometimes', 'array'],
            'diagrama.raias' => ['sometimes', 'array'],
            'diagrama.raias.*.id' => ['sometimes', 'string', 'max:50'],
            'diagrama.raias.*.nome' => ['sometimes', 'string', 'max:80'],
            'diagrama.raias.*.ordem' => ['sometimes', 'integer', 'min:0'],
            'diagrama.raias.*.altura' => ['sometimes', 'integer', 'min:140', 'max:600'],
            'diagrama.viewport' => ['sometimes', 'array'],
            'diagrama.viewport.x' => ['sometimes', 'numeric'],
            'diagrama.viewport.y' => ['sometimes', 'numeric'],
            'diagrama.viewport.zoom' => ['sometimes', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título do fluxograma.',
            'titulo.max' => 'O título pode ter no máximo 100 caracteres.',
            'descricao.max' => 'A descrição pode ter no máximo 2000 caracteres.',
            'tipo.in' => 'O tipo deve ser linear ou funcional.',
        ];
    }
}
