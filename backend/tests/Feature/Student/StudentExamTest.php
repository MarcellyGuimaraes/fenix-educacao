<?php

namespace Tests\Feature\Student;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = Student::factory()->create();
    }

    /**
     * @return array<string, string>
     */
    private function headers(?Student $student = null): array
    {
        $student ??= $this->student;

        return ['X-User-Role' => 'student', 'X-User-Id' => (string) $student->id];
    }

    /**
     * Cria uma prova com N questões, cada uma com uma alternativa correta e uma errada.
     */
    private function makeExam(int $questions = 2): Exam
    {
        $exam = Exam::factory()->create(['teacher_id' => Teacher::factory()]);

        for ($i = 1; $i <= $questions; $i++) {
            $question = $exam->questions()->create(['statement' => "Questão {$i}", 'order' => $i]);
            $question->options()->createMany([
                ['text' => 'Correta', 'is_correct' => true, 'order' => 1],
                ['text' => 'Errada', 'is_correct' => false, 'order' => 2],
            ]);
        }

        return $exam->load('questions.options');
    }

    /**
     * Monta o payload de respostas escolhendo a alternativa correta ou errada por questão.
     *
     * @param  array<int, bool>  $correctMap  índice da questão => acertar?
     * @return array{answers: array<int, array{question_id: int, option_id: int}>}
     */
    private function answersFor(Exam $exam, array $correctMap): array
    {
        $answers = [];
        foreach ($exam->questions as $index => $question) {
            /** @var Question $question */
            $wantCorrect = $correctMap[$index] ?? true;
            $option = $question->options->firstWhere('is_correct', $wantCorrect);
            $answers[] = ['question_id' => $question->id, 'option_id' => $option->id];
        }

        return ['answers' => $answers];
    }

    public function test_lista_provas_disponiveis_sem_tentativa(): void
    {
        $this->makeExam();

        $this->getJson('/api/student/exams', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.attempted', false)
            ->assertJsonPath('data.0.attempt_id', null);
    }

    public function test_prova_do_aluno_nao_expoe_gabarito(): void
    {
        $exam = $this->makeExam(1);

        $response = $this->getJson("/api/student/exams/{$exam->id}", $this->headers())->assertOk();

        $option = $response->json('data.questions.0.options.0');
        $this->assertArrayNotHasKey('is_correct', $option);
    }

    public function test_submissao_corrige_automaticamente(): void
    {
        $exam = $this->makeExam(2);
        // acerta a primeira, erra a segunda -> 1/2 = 50%
        $payload = $this->answersFor($exam, [true, false]);

        $this->postJson("/api/student/exams/{$exam->id}/attempts", $payload, $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.score', 1)
            ->assertJsonPath('data.total_questions', 2)
            ->assertJsonPath('data.percentage', 50);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 1,
        ]);
        $this->assertDatabaseCount('attempt_answers', 2);
    }

    public function test_aluno_nao_pode_refazer_a_mesma_prova(): void
    {
        $exam = $this->makeExam(1);
        $payload = $this->answersFor($exam, [true]);

        $this->postJson("/api/student/exams/{$exam->id}/attempts", $payload, $this->headers())
            ->assertCreated();

        $this->postJson("/api/student/exams/{$exam->id}/attempts", $payload, $this->headers())
            ->assertStatus(409);
    }

    public function test_submissao_exige_todas_as_questoes(): void
    {
        $exam = $this->makeExam(2);
        $full = $this->answersFor($exam, [true, true]);
        $partial = ['answers' => [$full['answers'][0]]];

        $this->postJson("/api/student/exams/{$exam->id}/attempts", $partial, $this->headers())
            ->assertStatus(422);
    }

    public function test_ver_resultado_da_propria_tentativa(): void
    {
        $exam = $this->makeExam(1);
        $payload = $this->answersFor($exam, [true]);

        $attemptId = $this->postJson("/api/student/exams/{$exam->id}/attempts", $payload, $this->headers())
            ->json('data.id');

        $this->getJson("/api/student/attempts/{$attemptId}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.percentage', 100)
            ->assertJsonStructure(['data' => ['answers' => [['question_id', 'is_correct', 'correct_option_text']]]]);
    }

    public function test_aluno_nao_ve_tentativa_de_outro(): void
    {
        $exam = $this->makeExam(1);
        $payload = $this->answersFor($exam, [true]);

        $attemptId = $this->postJson("/api/student/exams/{$exam->id}/attempts", $payload, $this->headers())
            ->json('data.id');

        $other = Student::factory()->create();

        $this->getJson("/api/student/attempts/{$attemptId}", $this->headers($other))
            ->assertForbidden();
    }
}
