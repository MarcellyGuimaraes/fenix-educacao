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

    /**
     * Tentativa numa prova arbitrária, com aluno opcional.
     */
    private function attemptOn(Exam $exam, float $percentage, ?Student $student = null, int $score = 0): ExamAttempt
    {
        return ExamAttempt::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student?->id ?? Student::factory(),
            'score' => $score,
            'total_questions' => 10,
            'percentage' => $percentage,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function headersFor(Teacher $teacher): array
    {
        return ['X-User-Role' => 'teacher', 'X-User-Id' => (string) $teacher->id];
    }

    public function test_resumo_e_ranking_ignoram_provas_de_outro_professor(): void
    {
        $this->attempt(100, 10);
        $otherExam = Exam::factory()->create(['teacher_id' => Teacher::factory()]);
        $this->attemptOn($otherExam, 0);

        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.total_attempts', 1)
            ->assertJsonPath('data.average_percentage', 100)
            ->assertJsonPath('data.best.percentage', 100)
            ->assertJsonPath('data.worst.percentage', 100);

        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.exam_title', $this->exam->title);
    }

    public function test_cache_do_dashboard_nao_vaza_entre_professores(): void
    {
        $this->attempt(100, 10);
        $other = Teacher::factory()->create();
        $otherExam = Exam::factory()->create(['teacher_id' => $other->id]);
        $this->attemptOn($otherExam, 20);
        $this->attemptOn($otherExam, 40);

        // A consulta primeiro (popula o cache dele)...
        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 1);
        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonPath('meta.total', 1);

        // ...e B continua vendo só as próprias métricas.
        $this->getJson('/api/dashboard/summary', $this->headersFor($other))
            ->assertJsonPath('data.total_attempts', 2)
            ->assertJsonPath('data.average_percentage', 30);
        $this->getJson('/api/dashboard/ranking', $this->headersFor($other))
            ->assertJsonPath('meta.total', 2);
    }

    public function test_professor_sem_tentativas(): void
    {
        $this->getJson('/api/dashboard/summary', $this->headers())
            ->assertJsonPath('data.total_attempts', 0)
            ->assertJsonPath('data.average_percentage', 0)
            ->assertJsonPath('data.best', null)
            ->assertJsonPath('data.worst', null);

        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/dashboard/students', $this->headers())
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_metricas_por_prova(): void
    {
        $this->exam->update(['title' => 'Matemática']);
        $this->attempt(100, 10);
        $this->attempt(50, 5);
        $this->attempt(0, 0);

        Exam::factory()->create(['teacher_id' => $this->teacher->id, 'title' => 'Artes']);
        Exam::factory()->create(['teacher_id' => Teacher::factory(), 'title' => 'Alheia']);

        $response = $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Ordenado pelo título; prova sem tentativas vem com null.
        $response->assertJsonPath('data.0.exam_title', 'Artes')
            ->assertJsonPath('data.0.attempts_count', 0)
            ->assertJsonPath('data.0.average_percentage', null)
            ->assertJsonPath('data.0.best_percentage', null)
            ->assertJsonPath('data.0.worst_percentage', null);

        $response->assertJsonPath('data.1.exam_id', $this->exam->id)
            ->assertJsonPath('data.1.exam_title', 'Matemática')
            ->assertJsonPath('data.1.attempts_count', 3);
        $this->assertEquals(50, $response->json('data.1.average_percentage'));
        $this->assertEquals(100, $response->json('data.1.best_percentage'));
        $this->assertEquals(0, $response->json('data.1.worst_percentage'));
    }

    public function test_media_por_prova_arredonda_em_duas_casas(): void
    {
        $this->attempt(100);
        $this->attempt(33.33);
        $this->attempt(0);

        $response = $this->getJson('/api/dashboard/exams', $this->headers());

        $this->assertEquals(44.44, $response->json('data.0.average_percentage'));
    }

    public function test_aluno_versus_media(): void
    {
        $ana = Student::factory()->create(['name' => 'Ana']);
        $bruno = Student::factory()->create(['name' => 'Bruno']);
        Student::factory()->create(['name' => 'Sem tentativas']);
        $secondExam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->attemptOn($this->exam, 100, $ana);
        $this->attemptOn($secondExam, 80, $ana);
        $this->attemptOn($this->exam, 30, $bruno);

        // Tentativa do Bruno em prova de outro professor não entra na conta.
        $this->attemptOn(Exam::factory()->create(['teacher_id' => Teacher::factory()]), 100, $bruno);

        $response = $this->getJson('/api/dashboard/students', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $response->assertJsonPath('data.0.student_id', $ana->id)
            ->assertJsonPath('data.0.student_name', 'Ana')
            ->assertJsonPath('data.0.attempts_count', 2);
        $this->assertEquals(90, $response->json('data.0.average_percentage'));
        $this->assertEquals(20, $response->json('data.0.difference_from_average'));

        $response->assertJsonPath('data.1.student_name', 'Bruno')
            ->assertJsonPath('data.1.attempts_count', 1);
        $this->assertEquals(30, $response->json('data.1.average_percentage'));
        $this->assertEquals(-40, $response->json('data.1.difference_from_average'));
    }

    public function test_aluno_versus_media_empate_ordena_por_nome_e_pagina(): void
    {
        foreach (['Carla', 'Ana', 'Bia'] as $name) {
            $this->attemptOn($this->exam, 50, Student::factory()->create(['name' => $name]));
        }

        $this->getJson('/api/dashboard/students?per_page=2&page=1', $this->headers())
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.student_name', 'Ana')
            ->assertJsonPath('data.1.student_name', 'Bia');

        $this->getJson('/api/dashboard/students?per_page=2&page=2', $this->headers())
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_name', 'Carla');
    }

    public function test_ranking_filtrado_por_prova(): void
    {
        $this->attempt(100, 10);
        $other = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
        $this->attemptOn($other, 90, score: 9);
        $this->attemptOn($other, 70, score: 7);

        $response = $this->getJson("/api/dashboard/ranking?exam_id={$other->id}&per_page=1", $this->headers())
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.position', 1)
            ->assertJsonPath('data.0.exam_title', $other->title);
        $this->assertEquals(90, $response->json('data.0.percentage'));

        $this->getJson("/api/dashboard/ranking?exam_id={$other->id}&per_page=1&page=2", $this->headers())
            ->assertJsonPath('data.0.position', 2);

        // Sem filtro: todas as provas do professor.
        $this->getJson('/api/dashboard/ranking', $this->headers())
            ->assertJsonPath('meta.total', 3);
    }

    public function test_ranking_com_exam_id_invalido_retorna_422(): void
    {
        foreach (['abc', '0', '-1', '99999999999999999999'] as $value) {
            $this->getJson("/api/dashboard/ranking?exam_id={$value}", $this->headers())
                ->assertStatus(422)
                ->assertJsonValidationErrors('exam_id');
        }
    }

    public function test_ranking_com_prova_inexistente_retorna_404(): void
    {
        $this->getJson('/api/dashboard/ranking?exam_id=999999', $this->headers())
            ->assertNotFound();
    }

    public function test_ranking_com_prova_de_outro_professor_retorna_403(): void
    {
        $otherExam = Exam::factory()->create(['teacher_id' => Teacher::factory()]);
        $this->attemptOn($otherExam, 100);

        $this->getJson("/api/dashboard/ranking?exam_id={$otherExam->id}", $this->headers())
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta prova pertence a outro professor.');
    }

    public function test_novos_endpoints_refletem_nova_tentativa_apos_invalidar_cache(): void
    {
        $this->attempt(100, 10);

        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonPath('data.0.attempts_count', 1);
        $this->getJson('/api/dashboard/students', $this->headers())
            ->assertJsonPath('meta.total', 1);

        $student = Student::factory()->create();
        $question = $this->exam->questions()->create(['statement' => 'Q?', 'order' => 1]);
        $wrong = $question->options()->create(['text' => 'Errada', 'is_correct' => false, 'order' => 1]);
        $question->options()->create(['text' => 'Certa', 'is_correct' => true, 'order' => 2]);

        $this->postJson(
            "/api/student/exams/{$this->exam->id}/attempts",
            ['answers' => [['question_id' => $question->id, 'option_id' => $wrong->id]]],
            ['X-User-Role' => 'student', 'X-User-Id' => (string) $student->id],
        )->assertCreated();

        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonPath('data.0.attempts_count', 2);
        $this->getJson('/api/dashboard/students', $this->headers())
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(string $title): array
    {
        return [
            'title' => $title,
            'questions' => [[
                'statement' => 'Q?',
                'options' => [
                    ['text' => 'Certa', 'is_correct' => true],
                    ['text' => 'Errada', 'is_correct' => false],
                ],
            ]],
        ];
    }

    public function test_criar_prova_invalida_o_cache_do_dashboard(): void
    {
        // Primeira leitura popula o cache com a prova do setUp.
        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonCount(1, 'data');

        $this->postJson('/api/exams', $this->examPayload('Prova nova'), $this->headers())
            ->assertCreated();

        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['exam_title' => 'Prova nova', 'attempts_count' => 0]);
    }

    public function test_editar_prova_invalida_o_cache_do_dashboard(): void
    {
        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonPath('data.0.exam_title', $this->exam->title);

        $this->putJson("/api/exams/{$this->exam->id}", $this->examPayload('Título novo'), $this->headers())
            ->assertOk();

        $this->getJson('/api/dashboard/exams', $this->headers())
            ->assertJsonPath('data.0.exam_title', 'Título novo');
    }
}
