<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VincularCursoExecucaoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    public function rules(): array
    {
        return [
            'curso_id' => ['required', 'integer', Rule::exists('cursos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'curso_id.required' => 'Selecione o curso oficial para vincular este registro.',
            'curso_id.exists' => 'Curso não encontrado no catálogo.',
        ];
    }
}
