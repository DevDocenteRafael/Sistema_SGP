<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\UnidadeOferta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CursoPorEixoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    public function rules(): array
    {
        return [
            'curso' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos_tecnologicos'))],
            'unidade' => ['nullable', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'ano' => ['required', 'string', 'max:4', Rule::in(config('curso_por_eixos.anos'))],
            'ch' => ['nullable', 'string', 'max:50'],
            'turmas' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'alunos' => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'instrutores' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('curso_por_eixos.status'))],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'is_novo' => ['nullable', 'boolean'],
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'curso.required' => 'O nome do curso é obrigatório.',
            'eixo.required' => 'Selecione o eixo tecnológico.',
            'eixo.in' => 'Selecione um eixo válido.',
            'ano.required' => 'Selecione o ano.',
            'ano.in' => 'Ano inválido.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'turmas.regex' => 'Turmas deve conter apenas números.',
            'alunos.regex' => 'Alunos deve conter apenas números.',
        ];
    }
}
