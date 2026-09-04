<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\UnidadeOferta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnidadeOfertaRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->has('ativo')) {
            $this->merge([
                'ativo' => filter_var($this->input('ativo'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->filled('nome')) {
            $this->merge(['nome' => trim((string) $this->input('nome'))]);
        }

        if ($this->filled('tipo')) {
            $this->merge(['tipo' => strtolower(trim((string) $this->input('tipo')))]);
        }
    }

    public function rules(): array
    {
        $unidade = $this->route('unidade_ofertum')
            ?? $this->route('unidade_oferta')
            ?? $this->route('unidadeOferta');
        $unidadeId = $unidade instanceof UnidadeOferta ? $unidade->id : $unidade;
        $regiaoId = $this->input('regiao_administrativa_id');

        return [
            'regiao_administrativa_id' => [
                'required',
                'integer',
                Rule::exists('regioes_administrativas', 'id'),
            ],
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('unidades_oferta', 'nome')
                    ->where(fn ($q) => $q->where('regiao_administrativa_id', $regiaoId))
                    ->ignore($unidadeId),
            ],
            'tipo' => ['required', 'string', Rule::in(UnidadeOferta::TIPOS)],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'regiao_administrativa_id.required' => 'Selecione a região administrativa.',
            'regiao_administrativa_id.exists' => 'Região administrativa inválida.',
            'nome.required' => 'Informe o nome da unidade.',
            'nome.unique' => 'Já existe uma unidade com este nome nesta região.',
            'tipo.required' => 'Selecione o tipo da unidade.',
            'tipo.in' => 'Tipo inválido. Use CEP, Polo ou Faculdade.',
        ];
    }
}
