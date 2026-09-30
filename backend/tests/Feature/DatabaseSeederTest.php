<?php

namespace Tests\Feature;

use App\Models\Teacher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_cria_tentativas_e_pode_rodar_mais_de_uma_vez(): void
    {
        // O container roda `migrate --seed` a cada subida.
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('exams', 3);
        $this->assertDatabaseCount('exam_attempts', 8);

        $teacher = Teacher::where('name', 'Prof. Ana Souza')->firstOrFail();
        $headers = ['X-User-Role' => 'teacher', 'X-User-Id' => (string) $teacher->id];

        $this->getJson('/api/dashboard/summary', $headers)
            ->assertJsonPath('data.total_attempts', 6);

        // Maria só fez a prova difícil e foi a melhor nela: lidera o Aluno × média.
        $this->getJson('/api/dashboard/students', $headers)
            ->assertJsonPath('data.0.student_name', 'Maria Oliveira');
    }
}
