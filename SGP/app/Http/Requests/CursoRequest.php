<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    protected function prepareForValidation(): void
    {
        $unidades = $this->input('unidades_oferta');

        if (is_array($unidades) && count($unidades) > 0) {
            $this->merge([
                'unidade' => $unidades[0],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'ciclo_id' => ['nullable', 'integer', Rule::exists('portfolio_ciclos', 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'eixo' => ['required', 'string', 'max:150', Rule::in(config('eixos'))],
            'modalidade' => ['nullable', 'string', 'max:100', Rule::in(config('cursos.modalidades'))],
            'carga_horaria' => ['nullable', 'string', 'max:50'],
            'turmas' => ['nullable', 'string', 'max:20'],
            'codigo_processo' => ['nullable', 'string', 'max:100'],
            'alunos' => ['nullable', 'string', 'max:20'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'codigo_dn' => ['nullable', 'string', 'max:50'],
            'codigo_sig' => ['nullable', 'string', 'max:100'],
            'identificacao' => ['nullable', 'string', 'max:50'],
            'tipo' => ['nullable', 'string', 'max:100', Rule::in(config('cursos.tipos'))],
            'status' => ['required', 'string', 'max:50', Rule::in(config('cursos.status'))],
            'ultima_revisao' => ['nullable', 'string', 'max:50'],
            'processo_sei' => ['nullable', 'string', 'max:100'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'unidade' => ['nullable', 'string', 'max:100', Rule::in(config('unidades'))],
            'unidades_oferta' => ['nullable', 'array'],
            'unidades_oferta.*' => ['string', Rule::in(config('unidades'))],
            'observacoes' => ['nullable', 'string'],
            'valores' => ['nullable', 'string', 'max:255'],
            'compativel_bolsa' => ['nullable', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'comercial' => ['nullable', 'string', 'max:10', Rule::in(config('cursos.sim_nao'))],
            'pcn' => ['nullable', 'string', 'max:255'],
            'pcr' => ['nullable', 'string', 'max:255'],
            'justificativa_duplicidade' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título do curso é obrigatório.',
            'eixo.required' => 'Selecione o segmento / área.',
            'eixo.in' => 'Selecione um segmento válido.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'ciclo_id.exists' => 'Ciclo de portfólio inválido.',
            'unidade.in' => 'Selecione uma unidade válida.',
            'unidades_oferta.*.in' => 'Selecione unidades válidas.',
            'modalidade.in' => 'Selecione uma modalidade válida.',
            'tipo.in' => 'Selecione um tipo válido.',
            'compativel_bolsa.in' => 'Selecione SIM ou NÃO.',
            'comercial.in' => 'Selecione SIM ou NÃO.',
            'data_fim.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
