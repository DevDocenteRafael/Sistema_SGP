<?php

namespace App\Http\Requests;

use App\Models\Pca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sei') && ! $this->filled('numero_sei')) {
            $this->merge(['numero_sei' => $this->input('sei')]);
        }

        if ($this->filled('sig') && ! $this->filled('codigo_sig')) {
            $this->merge(['codigo_sig' => $this->input('sig')]);
        }

        if ($this->filled('ch') && ! $this->filled('carga_horaria')) {
            $this->merge(['carga_horaria' => $this->input('ch')]);
        }

        if ($this->filled('curso') && ! $this->filled('titulo')) {
            $this->merge(['titulo' => $this->input('curso')]);
        }
    }

    public function rules(): array
    {
        $pca = $this->route('pca');
        $pcaId = $pca instanceof Pca ? $pca->id : $pca;

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'semestre' => ['nullable', 'string', 'max:20'],
            'numero_sei' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pcas', 'numero_sei')->ignore($pcaId),
            ],
            'codigo_sig' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pcas', 'codigo_sig')->ignore($pcaId),
            ],
            'eixo' => ['nullable', 'string', 'max:100'],
            'unidade' => ['nullable', 'string', 'max:100'],
            'carga_horaria' => ['nullable', 'string', 'max:50'],
            'precificacao' => ['nullable', 'string', 'max:100'],
            'valor_primeiro_modulo' => ['nullable', 'string', 'max:50'],
            'valor' => ['nullable', 'string', 'max:50'],
            'parcelas_boleto' => ['nullable', 'string', 'max:50'],
            'valor_parcela_boleto' => ['nullable', 'string', 'max:50'],
            'parcelas_cartao' => ['nullable', 'string', 'max:50'],
            'valor_cartao' => ['nullable', 'string', 'max:50'],
            'parcela_desc_20' => ['nullable', 'string', 'max:50'],
            'parcela_desc_15' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'observacao' => ['nullable', 'string'],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título / curso é obrigatório.',
            'numero_sei.unique' => 'Este número SEI já está cadastrado.',
            'codigo_sig.unique' => 'Este código SIG já está cadastrado.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
