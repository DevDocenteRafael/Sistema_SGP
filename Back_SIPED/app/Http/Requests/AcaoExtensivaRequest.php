<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcaoExtensivaRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        if ($this->filled('numero_processo_sei')) {
            $this->merge([
                'numero_processo_sei' => ProcessoSeiValido::sanitizar($this->input('numero_processo_sei')),
            ]);
        }

        $this->canonicalizarEixoInput();
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'priorizacao' => ['required', 'string', 'max:20', Rule::in(config('acoes_extensivas.priorizacoes'))],
            'atribuido' => ['required', 'string', 'max:100'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos'))],
            'numero_processo_sei' => ['required', 'string', 'max:100', new ProcessoSeiValido(obrigatorio: true, rotulo: 'Número do processo SEI')],
            'tipo' => ['required', 'string', 'max:100', Rule::in(config('acoes_extensivas.tipos'))],
            'assunto' => ['required', 'string', 'max:500'],
            'objetivo' => ['required', 'string', 'max:2000'],
            // Setor/etapa atual (CPED, DEP, DIREG, NC) — separado da prioridade e do status.
            'setor_atual' => ['required', 'string', 'max:50', Rule::in(config('acoes_extensivas.setores'))],
            // Status de execução: só validado quando a lista for definida com o cliente.
            'status' => config('acoes_extensivas.status_execucao')
                ? ['nullable', 'string', 'max:50', Rule::in(config('acoes_extensivas.status_execucao'))]
                : ['nullable', 'string', 'max:50'],
            'ultima_atualizacao' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'setor_atual.required' => 'Informe o setor/etapa atual.',
            'setor_atual.in' => 'Selecione um setor/etapa válido (CPED, DEP, DIREG ou NC).',
            'priorizacao.in' => 'A prioridade deve ser Baixa, Média ou Alta.',
            'objetivo.required' => 'Informe o objetivo.',
            'ultima_atualizacao.required' => 'Informe a última atualização.',
            'priorizacao.required' => 'A priorização é obrigatória.',
            'priorizacao.in' => 'Priorização inválida.',
            'atribuido.required' => 'Informe o responsável atribuído.',
            'eixo.required' => 'O eixo é obrigatório.',
            'eixo.in' => 'Eixo inválido.',
            'numero_processo_sei.required' => 'O número do processo SEI é obrigatório.',
            'numero_processo_sei.regex' => 'O processo SEI deve conter apenas números, pontos, barras ou hífens.',
            'tipo.required' => 'O tipo é obrigatório.',
            'tipo.in' => 'Tipo inválido.',
            'assunto.required' => 'O assunto é obrigatório.',
        ];
    }
}
