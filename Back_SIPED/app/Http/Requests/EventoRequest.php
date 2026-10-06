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
        if ($this->filled('processo_sei')) {
            $this->merge(['processo_sei' => \App\Rules\ProcessoSeiValido::sanitizar($this->input('processo_sei'))]);
        }
        if ($this->has('tipo_evento')) {
            $this->merge(['tipo_evento' => self::normalizarTipo($this->input('tipo_evento'))]);
        }

        $this->canonicalizarEixoInput();
    }

    /**
     * Texto controlado: espaços normalizados e, se já existir um tipo igual
     * (sem diferenciar maiúsculas/acentos), reaproveita a grafia existente.
     */
    public static function normalizarTipo(mixed $valor): ?string
    {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $valor) ?? '');
        if ($texto === '') {
            return null;
        }

        $chave = \App\Support\CatalogoOficial::chave($texto);
        $conhecidos = array_merge(
            config('eventos.tipos_sugeridos', []),
            \App\Models\Evento::query()->whereNotNull('tipo_evento')->distinct()->pluck('tipo_evento')->all(),
        );
        foreach ($conhecidos as $conhecido) {
            if (\App\Support\CatalogoOficial::chave($conhecido) === $chave) {
                return $conhecido;
            }
        }

        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_substr($texto, 1);
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'nome' => ['required', 'string', 'max:200'],
            // Todo evento gera processo SEI (reunião com a CPED).
            'processo_sei' => ['required', 'string', 'max:100', new \App\Rules\ProcessoSeiValido(obrigatorio: true)],
            // Texto controlado até existir catálogo oficial de tipos.
            'tipo_evento' => ['required', 'string', 'max:100'],
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
            'processo_sei.required' => 'Informe o processo SEI do evento.',
            'tipo_evento.required' => 'Informe o tipo do evento.',
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
