<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Models\PortfolioCiclo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioCicloGerarRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    protected function prepareForValidation(): void
    {
        if ($this->input('observacao') === '') {
            $this->merge(['observacao' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'origem_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'nome' => ['required', 'string', 'max:80', Rule::unique('portfolio_ciclos', 'nome')],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'marcar_atual' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do novo ciclo de portfólio.',
            'nome.max' => 'O nome deve ter no máximo 80 caracteres.',
            'nome.unique' => 'Já existe um ciclo com este nome.',
            'origem_id.exists' => 'Ciclo de origem inválido.',
            'observacao.max' => 'A observação deve ter no máximo 2000 caracteres.',
        ];
    }

    public function origem(): PortfolioCiclo
    {
        $origemId = $this->validated('origem_id');

        if ($origemId) {
            return PortfolioCiclo::query()->findOrFail($origemId);
        }

        $atual = PortfolioCiclo::atual();

        if (! $atual) {
            abort(422, 'Não há ciclo de origem para copiar.');
        }

        return $atual;
    }
}
