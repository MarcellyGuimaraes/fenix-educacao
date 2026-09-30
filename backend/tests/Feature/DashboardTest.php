<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::factory()->create();
        $this->exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return ['X-User-Role' => 'teacher', 'X-User-Id' => (string) $this->teacher->id];
    }

    private function attempt(float $percentage, int $score = 0, int $total = 10): ExamAttempt
    {
        return ExamAttempt::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => Student::factory(),
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $percentage,
        ]);
    }

    public function test_summary_calcula_media_melhor_e_pior(): void
    {
        $this->attempt(100, 10);
        $this->attempt(50, 5);
        $this->attempt(0, 0);

        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.average_percentage', 50)
            ->assertJsonPath('data.total_attempts', 3)
            ->assertJsonPath('data.best.percentage', 100)
            ->assertJsonPath('data.worst.percentage', 0);
    }

    public function test_ranking_ordenado_e_paginado(): void
    {
        $this->attempt(100, 10);
        $this->attempt(80, 8);
        $this->attempt(60, 6);

        $response = $this->getJson('/api/dashboard/ranking?per_page=2&page=1', $this->headers())
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(2, 'data');

        $this->assertSame(1, $response->json('data.0.position'));
        $this->assertEquals(100, $response->json('data.0.percentage'));
        $this->assertEquals(80, $response->json('data.1.percentage'));
    }

    public function test_summary_reflete_nova_tentativa_apos_invalidar_cache(): void
    {
        $this->attempt(100, 10);

        // Primeira leitura popula o cache.
        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 1);

        // Nova tentativa via API deve invalidar o cache do dashboard.
        $student = Student::factory()->create();
        $question = $this->exam->questions()->create(['statement' => 'Q?', 'order' => 1]);
        $correct = $question->options()->create(['text' => 'Certa', 'is_correct' => true, 'order' => 1]);
        $question->options()->create(['text' => 'Errada', 'is_correct' => false, 'order' => 2]);

        $this->postJson(
            "/api/student/exams/{$this->exam->id}/attempts",
            ['answers' => [['question_id' => $question->id, 'option_id' => $correct->id]]],
            ['X-User-Role' => 'student', 'X-User-Id' => (string) $student->id],
        )->assertCreated();

        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 2);
    }

    public function test_excluir_prova_invalida_o_cache_do_dashboard(): void
    {
        $this->attempt(100, 10);
        $this->attempt(50, 5);

        // Primeiras leituras populam o cache.
        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 2);
        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonPath('meta.total', 2);

        // Excluir a prova remove as tentativas em cascata e deve invalidar o cache.
        $this->deleteJson("/api/exams/{$this->exam->id}", [], $this->headers())
            ->assertNoContent();

        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 0)
            ->assertJsonPath('data.best', null);
        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }
}
