<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Http\Requests\Concerns\CanonicalizaCatalogoOficial;
use App\Models\Pca;
use App\Rules\ProcessoSeiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PcaRequest extends FormRequest
{
    use AutorizaEdicaoDados;
    use CanonicalizaCatalogoOficial;

    protected function prepareForValidation(): void
    {
        if ($this->filled('sei') && ! $this->filled('numero_sei')) {
            $this->merge(['numero_sei' => $this->input('sei')]);
        }

        if ($this->filled('sig') && ! $this->filled('codigo_sig')) {
            $this->merge(['codigo_sig' => $this->input('sig')]);
        }

        if ($this->filled('ch') && ! $this->filled('carga_horaria')) {
            $this->merge(['carga_horaria' => $this->input('ch')]);
        }

        if ($this->filled('curso') && ! $this->filled('titulo')) {
            $this->merge(['titulo' => $this->input('curso')]);
        }

        if ($this->filled('numero_sei')) {
            $this->merge([
                'numero_sei' => ProcessoSeiValido::sanitizar($this->input('numero_sei')),
            ]);
        }

        $this->canonicalizarEixoInput();
    }

    public function rules(): array
    {
        $pca = $this->route('pca');
        $pcaId = $pca instanceof Pca ? $pca->id : $pca;

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'semestre' => ['required', 'string', 'max:20'],
            'numero_sei' => [
                'required',
                'string',
                'max:100',
                new ProcessoSeiValido(obrigatorio: true, rotulo: 'Número SEI'),
                Rule::unique('pcas', 'numero_sei')->ignore($pcaId),
            ],
            'codigo_sig' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pcas', 'codigo_sig')->ignore($pcaId),
            ],
            'eixo' => ['required', 'string', 'max:100'],
            'unidade' => ['required', 'string', 'max:100'],
            'carga_horaria' => ['required', 'integer', 'min:1', 'max:99999'],
            'precificacao' => ['required', 'string', 'max:100'],
            'valor_primeiro_modulo' => ['required', 'string', 'max:50'],
            'valor' => ['required', 'string', 'max:50'],
            'parcelas_boleto' => ['required', 'integer', 'min:1', 'max:999'],
            'valor_parcela_boleto' => ['required', 'string', 'max:50'],
            'parcelas_cartao' => ['required', 'integer', 'min:1', 'max:999'],
            'valor_cartao' => ['required', 'string', 'max:50'],
            'parcela_desc_20' => ['required', 'string', 'max:50'],
            'parcela_desc_15' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'observacao' => ['required', 'string', 'max:2000'],
            'ano' => ['required', 'integer', 'min:1900', 'max:2100'],
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'observacao.required' => 'Preencha o campo Observação.',
            'parcela_desc_15.required' => 'Preencha o campo Parcela com desc. 15%.',
            'parcela_desc_20.required' => 'Preencha o campo Parcela com desc. 20%.',
            'valor_cartao.required' => 'Preencha o campo Valor Cartão.',
            'parcelas_cartao.required' => 'Preencha o campo Parcelas Cartão.',
            'valor_parcela_boleto.required' => 'Preencha o campo Valor Parcela Boleto.',
            'parcelas_boleto.required' => 'Preencha o campo Parcelas Boleto.',
            'valor.required' => 'Preencha o campo Valor Principal.',
            'valor_primeiro_modulo.required' => 'Preencha o campo Valor 1º Módulo.',
            'precificacao.required' => 'Preencha o campo Precificação.',
            'carga_horaria.required' => 'Preencha o campo CH.',
            'unidade.required' => 'Preencha o campo Estrutura Institucional.',
            'eixo.required' => 'Preencha o campo Eixo.',
            'codigo_sig.required' => 'Preencha o campo SIG.',
            'numero_sei.required' => 'Preencha o campo SEI.',
            'semestre.required' => 'Preencha o campo Semestre.',
            'ano.required' => 'Preencha o campo Ano.',
            'titulo.required' => 'O título / curso é obrigatório.',
            'numero_sei.unique' => 'Este número SEI já está cadastrado.',
            'codigo_sig.unique' => 'Este código SIG já está cadastrado.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
