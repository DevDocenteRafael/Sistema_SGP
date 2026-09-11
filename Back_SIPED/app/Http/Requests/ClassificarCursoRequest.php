<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AutorizaEdicaoDados;
use App\Support\CatalogoOficial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassificarCursoRequest extends FormRequest
{
    use AutorizaEdicaoDados;

    public function rules(): array
    {
        $eixo = (string) $this->input('eixo');
        $segmentos = CatalogoOficial::segmentosPorEixo()[$eixo] ?? [];

        return [
            'eixo' => ['required', 'string', Rule::in(CatalogoOficial::eixos())],
            'segmento' => ['nullable', 'string', Rule::in($segmentos)],
        ];
    }

    public function messages(): array
    {
        return [
            'eixo.required' => 'Selecione um dos cinco eixos oficiais.',
            'eixo.in' => 'Selecione um dos cinco eixos oficiais.',
            'segmento.in' => 'Selecione um segmento compatível com o eixo.',
        ];
    }
}
