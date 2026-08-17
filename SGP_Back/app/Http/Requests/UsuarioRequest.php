<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->podeGerenciarUsuarios() === true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $usuarioId = $usuario instanceof Usuario ? $usuario->id : $usuario;

        return [
            'nome' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'email')->ignore($usuarioId),
            ],
            'senha' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:6',
                'max:100',
            ],
            'cpf' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('usuarios', 'cpf')->ignore($usuarioId),
            ],
            'perfil' => [
                'required',
                Rule::in(config('permissoes.perfis')),
            ],
            'status' => ['sometimes', 'boolean'],
            'unidade' => ['nullable', 'string', 'max:100', Rule::in(config('unidades'))],
            'area' => ['nullable', 'string', 'max:100'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha.required' => 'A senha é obrigatória no cadastro.',
            'senha.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'perfil.required' => 'O perfil é obrigatório.',
            'perfil.in' => 'Perfil inválido. Use Administrador, Editor ou Consultor.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'unidade.in' => 'Selecione uma unidade válida.',
        ];
    }
}
