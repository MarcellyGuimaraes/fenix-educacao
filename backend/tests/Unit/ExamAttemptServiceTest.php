<?php

namespace Tests\Unit;

use App\Exceptions\ExamAlreadyAttemptedException;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamAttemptServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamAttemptService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExamAttemptService::class);
    }

    /**
     * Cria uma prova com N questões (1 correta + 1 errada por questão).
     */
    private function makeExam(int $questions): Exam
    {
        $exam = Exam::factory()->create(['teacher_id' => Teacher::factory()]);

        for ($i = 1; $i <= $questions; $i++) {
            $q = $exam->questions()->create(['statement' => "Q{$i}", 'order' => $i]);
            $q->options()->createMany([
                ['text' => 'Certa', 'is_correct' => true, 'order' => 1],
                ['text' => 'Errada', 'is_correct' => false, 'order' => 2],
            ]);
        }

        return $exam->load('questions.options');
    }

    /**
     * @param  array<int, bool>  $correct  acertar cada questão?
     * @return array<int, array{question_id: int, option_id: int}>
     */
    private function answers(Exam $exam, array $correct): array
    {
        $answers = [];
        foreach ($exam->questions as $i => $question) {
            $option = $question->options->firstWhere('is_correct', $correct[$i] ?? true);
            $answers[] = ['question_id' => $question->id, 'option_id' => $option->id];
        }

        return $answers;
    }

    public function test_calcula_pontuacao_e_percentual(): void
    {
        $exam = $this->makeExam(4);
        $student = Student::factory()->create();

        // 3 certas de 4 -> 75%
        $attempt = $this->service->submit($exam, $student, $this->answers($exam, [true, true, true, false]));

        $this->assertSame(3, $attempt->score);
        $this->assertSame(4, $attempt->total_questions);
        $this->assertEquals(75.0, (float) $attempt->percentage);
    }

    public function test_percentual_arredonda_para_duas_casas(): void
    {
        $exam = $this->makeExam(3);
        $student = Student::factory()->create();

        // 2 de 3 -> 66.67%
        $attempt = $this->service->submit($exam, $student, $this->answers($exam, [true, true, false]));

        $this->assertEquals(66.67, (float) $attempt->percentage);
    }

    public function test_lanca_excecao_em_tentativa_duplicada(): void
    {
        $exam = $this->makeExam(1);
        $student = Student::factory()->create();

        $this->service->submit($exam, $student, $this->answers($exam, [true]));

        $this->expectException(ExamAlreadyAttemptedException::class);
        $this->service->submit($exam, $student, $this->answers($exam, [true]));
    }

    public function test_lanca_validacao_quando_falta_questao(): void
    {
        $exam = $this->makeExam(2);
        $student = Student::factory()->create();

        $answers = $this->answers($exam, [true, true]);
        array_pop($answers); // remove uma resposta

        $this->expectException(ValidationException::class);
        $this->service->submit($exam, $student, $answers);
    }
}
