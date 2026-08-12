<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolucaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    public function rules(): array
    {
        $resolucao = $this->route('resolucao');
        $resolucaoId = $resolucao instanceof \App\Models\Resolucao ? $resolucao->id : $resolucao;

        return [
            'numero' => [
                'required',
                'string',
                'max:100',
                Rule::unique('resolucoes', 'numero')->ignore($resolucaoId),
            ],
            'curso_relacionado' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:120', Rule::in(config('resolucoes.categorias'))],
            'resumo' => ['required', 'string', 'max:1000'],
            'relator' => ['nullable', 'string', 'max:255'],
            'setor' => ['nullable', 'string', 'max:120', Rule::in(config('resolucoes.setores'))],
            'data_inicio_vigencia' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:50', Rule::in(config('resolucoes.status'))],
            'observacoes' => ['nullable', 'string'],
            'anexo_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'O número da resolução é obrigatório.',
            'numero.unique' => 'Já existe uma resolução com este número.',
            'resumo.required' => 'O resumo da resolução é obrigatório.',
            'data_inicio_vigencia.required' => 'A data de início da vigência é obrigatória.',
            'data_inicio_vigencia.date' => 'A data de início da vigência deve ser válida.',
            'categoria.in' => 'Categoria inválida.',
            'setor.in' => 'Setor inválido.',
            'status.in' => 'Status inválido.',
        ];
    }
}
