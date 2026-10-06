<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoraPedagogicaRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        if ($this->filled('eixo')) {
            $this->merge(['eixo' => \App\Support\CatalogoOficial::canonicalizarEixo((string) $this->input('eixo')) ?? $this->input('eixo')]);
        }
        // Segmento: só ajusta maiúsculas/acentos para a grafia oficial (não troca por eixo).
        if ($this->filled('segmento')) {
            $chave = \App\Support\CatalogoOficial::chave((string) $this->input('segmento'));
            foreach (\App\Support\CatalogoOficial::segmentos() as $oficial) {
                if (\App\Support\CatalogoOficial::chave($oficial) === $chave) {
                    $this->merge(['segmento' => $oficial]);
                    break;
                }
            }
        }

        if ($this->has('ativo')) {
            $ativo = $this->input('ativo');

            if (is_string($ativo)) {
                $this->merge([
                    'ativo' => filter_var($ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }

        if ($this->filled('processo_sei')) {
            $this->merge([
                'processo_sei' => ProcessoSeiValido::sanitizar($this->input('processo_sei')),
            ]);
        }

        if ($this->filled('matricula')) {
            $this->merge([
                'matricula' => preg_replace('/\D/', '', (string) $this->input('matricula')),
            ]);
        }

        $this->canonicalizarEixoInput();
    }

    /**
     * Status legado (Cancelada) só continua aceito em registro que já o tinha.
     *
     * @return list<string>
     */
    private function statusPermitidos(): array
    {
        $permitidos = config('horas_pedagogicas.status', []);
        $atual = $this->route('horaPedagogica');
        $statusAtual = $atual instanceof \App\Models\HoraPedagogica ? $atual->status : null;
        if ($statusAtual && in_array($statusAtual, config('horas_pedagogicas.status_legados', []), true)) {
            $permitidos[] = $statusAtual;
        }

        return $permitidos;
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'matricula' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
            'pessoa' => ['required', 'string', 'max:150'],
            // Segmento vem do catálogo de Segmentos e precisa pertencer ao Eixo escolhido.
            'segmento' => [
                'required',
                'string',
                'max:150',
                Rule::in(\App\Support\CatalogoOficial::segmentos()),
                function (string $atributo, mixed $valor, \Closure $falhar) {
                    $eixo = (string) $this->input('eixo');
                    $doEixo = \App\Support\CatalogoOficial::segmentosPorEixo()[$eixo] ?? null;
                    if ($doEixo !== null && ! in_array($valor, $doEixo, true)) {
                        $falhar('O segmento "'.$valor.'" não pertence ao eixo "'.$eixo.'".');
                    }
                },
            ],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos'))],
            'processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true)],
            'ano' => ['required', 'integer', Rule::in(array_map('intval', config('horas_pedagogicas.anos')))],
            'motivo' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in($this->statusPermitidos())],
            'ativo' => ['required', 'boolean'],
            'observacao' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacao.required' => 'Informe a observação.',
            'ativo.required' => 'Informe se o registro está ativo.',
            'matricula.required' => 'A matrícula é obrigatória.',
            'matricula.regex' => 'A matrícula deve conter apenas números.',
            'pessoa.required' => 'O nome da pessoa é obrigatório.',
            'segmento.required' => 'O segmento é obrigatório.',
            'segmento.in' => 'Selecione um segmento válido.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'ano.required' => 'O ano é obrigatório.',
            'ano.in' => 'Ano inválido.',
            'motivo.required' => 'O motivo é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ];
    }
}
