<?php

namespace Tests\Unit;

use App\Support\Identifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Teste unitário puro (sem Laravel): o formato de identificador aceito do
 * cliente é um inteiro positivo de até 18 dígitos, recebido como string.
 */
class IdentifierTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validIds(): array
    {
        return [
            'um' => ['1'],
            'comum' => ['42'],
            '18 dígitos' => ['999999999999999999'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidIds(): array
    {
        return [
            'zero' => ['0'],
            'negativo' => ['-1'],
            'zero à esquerda' => ['01'],
            'letras' => ['abc'],
            'decimal' => ['1.5'],
            'vazio' => [''],
            'espaço' => [' 1'],
            'injeção' => ['1 OR 1=1'],
            '19 dígitos' => ['1000000000000000000'],
            'null' => [null],
            'inteiro (não string)' => [1],
        ];
    }

    #[DataProvider('validIds')]
    public function test_aceita_ids_validos(string $id): void
    {
        $this->assertTrue(Identifier::isValid($id));
    }

    #[DataProvider('invalidIds')]
    public function test_rejeita_ids_invalidos(mixed $id): void
    {
        $this->assertFalse(Identifier::isValid($id));
    }
}
