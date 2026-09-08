<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoraPedagogicaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

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

        if ($this->filled('processo_sei')) {
            $this->merge([
                'processo_sei' => ProcessoSeiValido::sanitizar($this->input('processo_sei')),
            ]);
        }

        if ($this->filled('matricula')) {
            $this->merge([
                'matricula' => preg_replace('/\D/', '', (string) $this->input('matricula')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'matricula' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
            'pessoa' => ['required', 'string', 'max:150'],
            'segmento' => ['required', 'string', 'max:150', Rule::in(config('horas_pedagogicas.segmentos'))],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('horas_pedagogicas.eixos'))],
            'processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true)],
            'ano' => ['required', 'integer', Rule::in(array_map('intval', config('horas_pedagogicas.anos')))],
            'motivo' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('horas_pedagogicas.status'))],
            'ativo' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.required' => 'A matrícula é obrigatória.',
            'matricula.regex' => 'A matrícula deve conter apenas números.',
            'pessoa.required' => 'O nome da pessoa é obrigatório.',
            'segmento.required' => 'O segmento é obrigatório.',
            'segmento.in' => 'Selecione um segmento válido.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'ano.required' => 'O ano é obrigatório.',
            'ano.in' => 'Ano inválido.',
            'motivo.required' => 'O motivo é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ];
    }
}
