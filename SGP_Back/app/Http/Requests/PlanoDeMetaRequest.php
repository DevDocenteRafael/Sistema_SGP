<?php

namespace App\Http\Requests;

use App\Models\PlanoDeMeta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanoDeMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    protected function prepareForValidation(): void
    {
        $segmento = trim((string) $this->input('segmento', ''));
        $curso = trim((string) $this->input('curso', ''));

        if ($segmento !== '') {
            $this->merge([
                'segmento' => $segmento,
            ]);
        }

        if ($curso !== '') {
            $this->merge([
                'curso' => $curso,
            ]);
        }
    }

    public function rules(): array
    {
        $planoDeMeta = $this->route('plano_de_meta');
        $planoDeMetaId = $planoDeMeta instanceof PlanoDeMeta ? $planoDeMeta->id : $planoDeMeta;

        return [
            'segmento' => ['required', 'string', 'max:100'],
            'curso' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:100'],
            'numero_sei' => [
                'required',
                'string',
                'max:100',
                Rule::unique('plano_de_metas', 'numero_sei')->ignore($planoDeMetaId),
            ],
            'codigo_sig' => [
                'required',
                'string',
                'max:100',
                Rule::unique('plano_de_metas', 'codigo_sig')->ignore($planoDeMetaId),
            ],
            'mes_entrega' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'origem' => ['nullable', 'string', 'max:100'],
            'status_final' => ['required', 'string', 'max:50'],
            'observacao' => ['nullable', 'string'],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'segmento.required' => 'O segmento é obrigatório.',
            'curso.required' => 'O curso é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
            'numero_sei.required' => 'Informe o número SEI.',
            'numero_sei.unique' => 'Este número SEI já está cadastrado.',
            'codigo_sig.required' => 'Informe o código SIG.',
            'codigo_sig.unique' => 'Este código SIG já está cadastrado.',
            'mes_entrega.required' => 'Informe o mês de entrega.',
            'status.required' => 'Informe o status do registro.',
            'status_final.required' => 'Informe o status final.',
        ];
    }
}
