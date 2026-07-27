<?php

namespace App\Http\Requests;

use App\Models\Pca;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        $pca = $this->route('pca');
        $pcaId = $pca instanceof Pca ? $pca->id : $pca;

        return [
            'unidade' => ['required', 'string', 'max:100'],
            'curso' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:100'],
            'periodo' => ['required', 'string', 'max:100'],
            'numero_sei' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pcas', 'numero_sei')->ignore($pcaId),
            ],
            'codigo_sig' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pcas', 'codigo_sig')->ignore($pcaId),
            ],
            'status' => ['required', 'string', 'max:50'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'objetivo' => ['nullable', 'string'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string'],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'unidade.required' => 'A unidade é obrigatória.',
            'curso.required' => 'O curso é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
            'periodo.required' => 'O período é obrigatório.',
            'numero_sei.required' => 'Informe o número SEI.',
            'numero_sei.unique' => 'Este número SEI já está cadastrado.',
            'codigo_sig.required' => 'Informe o código SIG.',
            'codigo_sig.unique' => 'Este código SIG já está cadastrado.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
