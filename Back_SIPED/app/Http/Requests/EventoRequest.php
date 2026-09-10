<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Models\UnidadeOferta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        $this->canonicalizarEixoInput();
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'nome' => ['required', 'string', 'max:200'],
            'ano' => ['required', 'string', 'max:4', Rule::in(config('eventos.anos'))],
            'data' => ['required', 'date'],
            'unidade' => ['required', 'string', 'max:100', Rule::in(UnidadeOferta::nomesAtivos())],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eventos.eixos'))],
            'quantidade_pessoas' => ['required', 'integer', 'min:0', 'max:999999'],
            'equipe' => ['required', 'string', 'max:255'],
            'possui_acao_extensiva' => ['required', 'string', 'max:3', Rule::in(config('eventos.possui_acao_extensiva'))],
            'acao_vinculada' => [
                Rule::requiredIf(fn () => $this->input('possui_acao_extensiva') === 'Sim'),
                'nullable',
                'string',
                'max:255',
            ],
            'status' => ['required', 'string', 'max:50', Rule::in(config('eventos.status'))],
            'observacao' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacao.required' => 'Informe a observação.',
            'equipe.required' => 'Informe a equipe / responsáveis.',
            'quantidade_pessoas.required' => 'Informe a quantidade de pessoas.',
            'ano.required' => 'Informe o ano.',
            'nome.required' => 'Preencha o nome e a data do evento.',
            'data.required' => 'Preencha o nome e a data do evento.',
            'unidade.required' => 'A unidade é obrigatória.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'possui_acao_extensiva.required' => 'Informe se possui ação extensiva.',
            'possui_acao_extensiva.in' => 'Valor inválido para ação extensiva.',
            'acao_vinculada.required' => 'Informe a ação vinculada.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ];
    }
}
