<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\UnidadeOferta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CursoExecucaoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    public function rules(): array
    {
        $criando = $this->isMethod('post');

        return [
            'curso_id' => [$criando ? 'required' : 'nullable', 'integer', Rule::exists('cursos', 'id')],
            'curso' => ['nullable', 'string', 'max:255'],
            'unidade' => ['nullable', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'ano' => ['nullable', 'string', 'max:4'],
            'ch' => ['nullable', 'string', 'max:50'],
            'turmas' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'alunos' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'instrutores' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(config('curso_por_eixos.status'))],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'curso_id.required' => 'Selecione o curso oficial do catálogo.',
            'curso_id.exists' => 'Curso não encontrado no catálogo.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'status.in' => 'Situação inválida.',
            'turmas.regex' => 'Turmas deve conter apenas números.',
            'alunos.regex' => 'Alunos deve conter apenas números.',
        ];
    }
}
