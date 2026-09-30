<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ids malformados (header ou rota) nunca devem chegar ao banco: no PostgreSQL
 * isso estourava 500. O SQLite dos testes não reproduz o erro, então aqui
 * garantimos o contrato (401/404) — a query com o valor inválido nem é feita.
 */
class InvalidIdentifierTest extends TestCase
{
    use RefreshDatabase;

    private const TOO_BIG = '99999999999999999999';

    /**
     * @return array<string, array{string}>
     */
    public static function invalidHeaderIds(): array
    {
        return [
            'letras' => ['abc'],
            'injeção' => ['1 OR 1=1'],
            'vazio' => [''],
            'negativo' => ['-1'],
            'decimal' => ['1.5'],
            'zero' => ['0'],
            'acima do bigint' => [self::TOO_BIG],
        ];
    }

    #[DataProvider('invalidHeaderIds')]
    public function test_header_de_professor_invalido_retorna_401(string $id): void
    {
        $this->getJson('/api/exams', ['X-User-Role' => 'teacher', 'X-User-Id' => $id])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Professor não identificado.');
    }

    #[DataProvider('invalidHeaderIds')]
    public function test_header_de_aluno_invalido_retorna_401(string $id): void
    {
        $this->getJson('/api/student/exams', ['X-User-Role' => 'student', 'X-User-Id' => $id])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Aluno não identificado.');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidRouteIds(): array
    {
        return [
            'letras' => ['abc'],
            'zero' => ['0'],
            'acima do bigint' => [self::TOO_BIG],
        ];
    }

    #[DataProvider('invalidRouteIds')]
    public function test_prova_com_id_invalido_na_area_do_professor_retorna_404(string $id): void
    {
        $headers = ['X-User-Role' => 'teacher', 'X-User-Id' => (string) Teacher::factory()->create()->id];

        $this->getJson("/api/exams/{$id}", $headers)->assertNotFound()->assertJsonStructure(['message']);
        $this->putJson("/api/exams/{$id}", [], $headers)->assertNotFound();
        $this->deleteJson("/api/exams/{$id}", [], $headers)->assertNotFound();
    }

    #[DataProvider('invalidRouteIds')]
    public function test_ids_invalidos_na_area_do_aluno_retornam_404(string $id): void
    {
        $headers = ['X-User-Role' => 'student', 'X-User-Id' => (string) Student::factory()->create()->id];

        $this->getJson("/api/student/exams/{$id}", $headers)->assertNotFound()->assertJsonStructure(['message']);
        $this->postJson("/api/student/exams/{$id}/attempts", ['answers' => []], $headers)->assertNotFound();
        $this->getJson("/api/student/attempts/{$id}", $headers)->assertNotFound();
    }
}
