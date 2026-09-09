<?php

namespace App\Exceptions;

use InvalidArgumentException;

class ImportacaoInvalidaException extends InvalidArgumentException
{
    /**
     * @param  list<array<string, mixed>>  $erros
     */
    public function __construct(
        string $message,
        public readonly array $erros = [],
    ) {
        parent::__construct($message);
    }
}
