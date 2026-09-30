<?php

namespace App\Support;

/**
 * Formato aceito para identificadores vindos do cliente (parâmetros de rota e
 * header X-User-Id): inteiro positivo com até 18 dígitos — sempre abaixo do
 * limite de um bigint. Valores fora disso nunca chegam ao banco; no PostgreSQL
 * eles estourariam um erro de sintaxe/intervalo de inteiro (500).
 */
final class Identifier
{
    public const PATTERN = '[1-9][0-9]{0,17}';

    public static function isValid(mixed $value): bool
    {
        return is_string($value) && preg_match('/^'.self::PATTERN.'$/', $value) === 1;
    }
}
