<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\RegiaoAdministrativa;
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
            $tipo = strtolower(trim((string) $this->input('tipo')));
            // Aceita legado "cep" como "unidade"
            if ($tipo === 'cep') {
                $tipo = UnidadeOferta::TIPO_UNIDADE;
            }
            $this->merge(['tipo' => $tipo]);
        }

        foreach (['codigo', 'endereco', 'responsavel', 'motivo_inativacao'] as $campo) {
            if ($this->exists($campo) && $this->input($campo) !== null) {
                $valor = trim((string) $this->input($campo));
                $this->merge([$campo => $valor === '' ? null : $valor]);
            }
        }

        if ($this->filled('localidade')) {
            $nomeLocalidade = trim((string) $this->input('localidade'));
            if ($nomeLocalidade !== '') {
                $regiao = RegiaoAdministrativa::query()->firstOrCreate(
                    ['nome' => $nomeLocalidade],
                    ['ativo' => true],
                );
                $this->merge([
                    'localidade' => $nomeLocalidade,
                    'regiao_administrativa_id' => $regiao->id,
                ]);
            }
        }

        // Reativar limpa o motivo automaticamente.
        if ($this->has('ativo') && $this->boolean('ativo') === true) {
            $this->merge(['motivo_inativacao' => null]);
        }
    }

    public function rules(): array
    {
        $unidade = $this->route('unidade_ofertum')
            ?? $this->route('unidade_oferta')
            ?? $this->route('unidadeOferta');
        $unidadeId = $unidade instanceof UnidadeOferta ? $unidade->id : $unidade;
        $regiaoId = $this->input('regiao_administrativa_id');
        $inativando = $this->has('ativo') && $this->boolean('ativo') === false;

        return [
            'localidade' => ['nullable', 'string', 'max:100'],
            'regiao_administrativa_id' => [
                'required',
                'integer',
                Rule::exists('regioes_administrativas', 'id'),
            ],
            'nome' => [
                'required',
                'string',
                'max:180',
                Rule::unique('unidades_oferta', 'nome')
                    ->where(fn ($q) => $q->where('regiao_administrativa_id', $regiaoId))
                    ->ignore($unidadeId),
            ],
            'tipo' => ['required', 'string', Rule::in(UnidadeOferta::TIPOS)],
            'codigo' => ['nullable', 'string', 'max:50'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:150'],
            'ativo' => ['sometimes', 'boolean'],
            'motivo_inativacao' => [
                $inativando ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'regiao_administrativa_id.required' => 'Informe a localidade/região.',
            'regiao_administrativa_id.exists' => 'Localidade/região inválida.',
            'localidade.max' => 'A localidade deve ter no máximo 100 caracteres.',
            'nome.required' => 'Informe o nome da estrutura institucional.',
            'nome.unique' => 'Já existe uma estrutura com este nome nesta localidade.',
            'tipo.required' => 'Selecione o tipo de estrutura.',
            'tipo.in' => 'Tipo inválido. Use Faculdade, Polo ou Unidade.',
            'motivo_inativacao.required' => 'Informe o motivo da inativação.',
            'motivo_inativacao.max' => 'O motivo da inativação deve ter no máximo 2000 caracteres.',
        ];
    }
}
