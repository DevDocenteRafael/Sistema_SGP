<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Support\CatalogoOficial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CpedEquipeRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('ativo')) {
            $merge['ativo'] = filter_var($this->input('ativo'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($this->has('remover_foto')) {
            $merge['remover_foto'] = filter_var($this->input('remover_foto'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        foreach (['eixo_vinculado', 'cor', 'observacao', 'iniciais'] as $campo) {
            if ($this->exists($campo) && $this->input($campo) === '') {
                $merge[$campo] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        $this->canonicalizarEixoInput('eixo_vinculado');

        if ($this->filled('setor')) {
            $setorCanon = CatalogoOficial::canonicalizarEixo((string) $this->input('setor'));
            if ($setorCanon !== null) {
                $this->merge(['setor' => $setorCanon]);
            }
        }
    }

    public function rules(): array
    {
        $tiposComEixo = ['responsavel', 'instrutor'];

        return [
            'nome' => ['required', 'string', 'max:100'],
            'cargo' => ['required', 'string', 'max:100'],
            'setor' => ['required', 'string', 'max:100', Rule::in(config('cped_equipes.setores'))],
            'contato' => ['required', 'email', 'max:100'],
            'tipo' => ['required', 'string', 'max:50', Rule::in(config('cped_equipes.tipos'))],
            'eixo_vinculado' => [
                Rule::requiredIf(fn () => in_array($this->input('tipo'), $tiposComEixo, true)),
                'nullable',
                'string',
                'max:100',
                Rule::in(config('cped_equipes.eixos')),
            ],
            'iniciais' => ['required', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'remover_foto' => ['nullable', 'boolean'],
            'cor' => ['required', 'string', 'max:20'],
            'ativo' => ['required', 'boolean'],
            'observacao' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacao.required' => 'Informe a observação.',
            'ativo.required' => 'Informe se o membro está ativo.',
            'cor.required' => 'Informe a cor do avatar.',
            'iniciais.required' => 'Informe as iniciais do avatar.',
            'nome.required' => 'Informe o nome completo.',
            'cargo.required' => 'Informe o cargo / função.',
            'setor.required' => 'Selecione o setor / eixo.',
            'setor.in' => 'Setor / eixo inválido.',
            'contato.required' => 'Informe o e-mail de contato.',
            'contato.email' => 'Informe um e-mail válido.',
            'tipo.required' => 'Selecione o tipo do membro.',
            'tipo.in' => 'Tipo inválido.',
            'eixo_vinculado.required' => 'Informe o eixo vinculado para este tipo.',
            'eixo_vinculado.in' => 'Eixo vinculado inválido.',
            'foto.image' => 'A foto deve ser uma imagem válida.',
            'foto.max' => 'A foto deve ter no máximo 2 MB.',
        ];
    }
}
