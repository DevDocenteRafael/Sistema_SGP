<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProcessoSeiValido implements ValidationRule
{
    public function __construct(
        private readonly bool $obrigatorio = false,
        private readonly string $rotulo = 'Processo SEI',
    ) {}

    public static function sanitizar(?string $valor): string
    {
        $raw = trim((string) ($valor ?? ''));
        if ($raw === '') {
            return '';
        }

        $limpo = preg_replace('/[^0-9.\/-]/', '', $raw);

        return ($limpo === '' && $raw !== '') ? $raw : $limpo;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $original = trim((string) ($value ?? ''));
        $texto = preg_replace('/[^0-9.\/-]/', '', $original);

        if ($texto === '') {
            if ($this->obrigatorio) {
                $fail("{$this->rotulo} é obrigatório.");

                return;
            }

            if ($original !== '') {
                $fail("{$this->rotulo} deve conter apenas números, pontos, barras ou hífens, com ao menos um número.");
            }

            return;
        }

        if (! preg_match('/^[0-9.\/-]+$/', $texto) || ! preg_match('/\d/', $texto)) {
            $fail("{$this->rotulo} deve conter apenas números, pontos, barras ou hífens, com ao menos um número.");
        }
    }
}
