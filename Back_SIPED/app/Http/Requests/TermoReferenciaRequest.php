<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TermoReferenciaRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        if ($this->filled('processo_sei')) {
            $this->merge([
                'processo_sei' => ProcessoSeiValido::sanitizar($this->input('processo_sei')),
            ]);
        }

        $this->canonicalizarEixoInput();

        $merge = [];
        foreach (['numero_tr', 'numero_ata', 'data_vencimento_ata'] as $campo) {
            if ($this->exists($campo) && trim((string) $this->input($campo)) === '') {
                $merge[$campo] = null;
            }
        }
        if ($this->has('ata_renovada')) {
            $merge['ata_renovada'] = filter_var($this->input('ata_renovada'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos', []))],
            'processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true)],
            'prazo_deadline' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50', Rule::in(config('termos_referencia.status', ['Planejamento', 'Em Andamento', 'Em tramitação (fora da CPED)', 'Concluído', 'Arquivado']))],
            'observacao' => ['required', 'string', 'max:2000'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'concluido_em' => ['nullable', 'datetime'],
            // Dados da Ata derivada do TR (opcionais até a Ata existir).
            'numero_tr' => ['nullable', 'string', 'max:50'],
            'numero_ata' => ['nullable', 'string', 'max:100'],
            'data_vencimento_ata' => ['nullable', 'date'],
            'ata_renovada' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacao.required' => 'Informe as observações.',
            'data_fim.required' => 'Informe a data de término prevista.',
            'data_inicio.required' => 'Informe a data de início.',
            'nome.required' => 'O nome do Termo de Referência é obrigatório.',
            'nome.max' => 'O nome não pode exceder 255 caracteres.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Selecione um eixo válido.',
            'processo_sei.required' => 'O processo SEI é obrigatório.',
            'processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'prazo_deadline.required' => 'O prazo/deadline é obrigatório.',
            'prazo_deadline.date' => 'O prazo deve ser uma data válida.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'data_fim.after_or_equal' => 'A data de término deve ser posterior ou igual à data de início.',
            'numero_tr.max' => 'O número do TR deve ter no máximo 50 caracteres.',
            'numero_ata.max' => 'O número da Ata deve ter no máximo 100 caracteres.',
            'data_vencimento_ata.date' => 'A data de vencimento da Ata deve ser uma data válida.',
        ];
    }
}
