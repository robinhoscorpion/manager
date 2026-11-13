<?php

namespace App\Services\Signature;

use RuntimeException;

/**
 * Erro de assinatura digital com mensagem pronta para exibir ao usuário.
 */
class SignatureException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        // Mensagens podem trazer saída do sistema em outra codificação; sempre UTF-8 válido
        parent::__construct(\App\Support\Utf8::clean($message), $code, $previous);
    }
}
