<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolucaoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['status', 'categoria', 'setor', 'curso_relacionado', 'relator', 'observacoes', 'anexo_path'] as $campo) {
            if ($this->exists($campo) && $this->input($campo) === '') {
                $merge[$campo] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
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
            'curso_relacionado' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:120', Rule::in(config('resolucoes.categorias'))],
            'resumo' => ['required', 'string', 'max:1000'],
            'relator' => ['required', 'string', 'max:255'],
            'setor' => ['required', 'string', 'max:120', Rule::in(config('resolucoes.setores'))],
            'data_inicio_vigencia' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('resolucoes.status'))],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'anexo_path' => ['nullable', 'string', 'max:255'],
            'anexo' => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,odt,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'setor.required' => 'Informe o setor.',
            'relator.required' => 'Informe o relator.',
            'categoria.required' => 'Informe a categoria.',
            'curso_relacionado.required' => 'Informe o curso relacionado.',
            'status.required' => 'Informe o status.',
            'numero.required' => 'O número da resolução é obrigatório.',
            'numero.unique' => 'Já existe uma resolução com este número.',
            'resumo.required' => 'O resumo da resolução é obrigatório.',
            'data_inicio_vigencia.required' => 'A data de início da vigência é obrigatória.',
            'data_inicio_vigencia.date' => 'A data de início da vigência deve ser válida.',
            'categoria.in' => 'Categoria inválida.',
            'setor.in' => 'Setor inválido.',
            'status.in' => 'Status inválido.',
            'anexo.file' => 'O anexo deve ser um arquivo válido.',
            'anexo.max' => 'O anexo deve ter no máximo 5 MB.',
            'anexo.mimes' => 'O anexo deve ser PDF, Word, ODT ou imagem.',
        ];
    }
}
