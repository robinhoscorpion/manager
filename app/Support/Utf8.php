<?php

namespace App\Support;

/**
 * Garante texto em UTF-8 válido. Saídas de comandos do sistema (ex.: LibreOffice no Windows)
 * podem vir na codificação do console (CP850/Windows-1252) e quebram respostas JSON/Inertia.
 */
class Utf8
{
    public static function clean(?string $text): string
    {
        $text = (string) $text;

        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        foreach (['CP850', 'Windows-1252', 'ISO-8859-1'] as $encoding) {
            $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        // Último recurso: descarta bytes inválidos
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}
