<?php

namespace App\Services\Contracts;

use RuntimeException;

/**
 * Erro ao gerar o contrato (sem proposta, sem modelo, falha no Word ou na conversão para PDF).
 * O código da exceção segue o status HTTP adequado (404, 422, 500).
 */
class ContractGenerationException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        // Mensagens podem trazer saída do sistema em outra codificação; sempre UTF-8 válido
        parent::__construct(\App\Support\Utf8::clean($message), $code, $previous);
    }
}
