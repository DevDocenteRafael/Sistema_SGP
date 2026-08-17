<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoraPedagogicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ativo')) {
            $ativo = $this->input('ativo');

            if (is_string($ativo)) {
                $this->merge([
                    'ativo' => filter_var($ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'matricula' => ['required', 'string', 'max:50'],
            'pessoa' => ['required', 'string', 'max:150'],
            'segmento' => ['required', 'string', 'max:150', Rule::in(config('horas_pedagogicas.segmentos'))],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('horas_pedagogicas.eixos'))],
            'processo_sei' => ['required', 'string', 'max:100'],
            'ano' => ['required', 'integer', Rule::in(array_map('intval', config('horas_pedagogicas.anos')))],
            'motivo' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('horas_pedagogicas.status'))],
            'ativo' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.required' => 'A matrícula é obrigatória.',
            'pessoa.required' => 'O nome da pessoa é obrigatório.',
            'segmento.required' => 'O segmento é obrigatório.',
            'segmento.in' => 'Selecione um segmento válido.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'ano.required' => 'O ano é obrigatório.',
            'ano.in' => 'Ano inválido.',
            'motivo.required' => 'O motivo é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ];
    }
}
