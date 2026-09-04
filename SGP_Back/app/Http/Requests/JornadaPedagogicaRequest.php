<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JornadaPedagogicaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        $campos = [
            'data_inicio',
            'data_fim',
            'data_pre_jornada',
            'local',
            'espaco',
            'verba',
            'custos',
            'programacao',
            'setores',
            'observacoes',
        ];

        $merge = [];

        foreach ($campos as $campo) {
            if ($this->input($campo) === '') {
                $merge[$campo] = null;
            }
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'tem_pre_jornada' => ['required', 'string', 'max:3', Rule::in(config('jornadas_pedagogicas.sim_nao'))],
            'data_pre_jornada' => [
                Rule::requiredIf(fn () => $this->input('tem_pre_jornada') === 'Sim'),
                'nullable',
                'date',
            ],
            'local' => ['nullable', 'string', 'max:255'],
            'espaco' => ['nullable', 'string', 'max:255'],
            'verba' => ['nullable', 'string', 'max:100'],
            'custos' => ['nullable', 'string', 'max:2000'],
            'programacao' => ['nullable', 'string', 'max:2000'],
            'setores' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('jornadas_pedagogicas.status'))],
            'anexo' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,odt,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título da jornada é obrigatório.',
            'data_pre_jornada.required' => 'Informe a data da pré-jornada.',
            'tem_pre_jornada.required' => 'Informe se há pré-jornada.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'anexo.max' => 'O anexo deve ter no máximo 5 MB.',
            'anexo.mimes' => 'O anexo deve ser PDF, Word, ODT ou imagem.',
        ];
    }
}
