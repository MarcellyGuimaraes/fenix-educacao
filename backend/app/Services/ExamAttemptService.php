<?php

namespace App\Services;

use App\Exceptions\ExamAlreadyAttemptedException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Repositories\Contracts\ExamAttemptRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function __construct(
        private readonly ExamAttemptRepositoryInterface $attempts,
        private readonly ExamRepositoryInterface $exams,
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * Provas disponíveis para o aluno, com a tentativa dele (se existir).
     *
     * @return Collection<int, Exam>
     */
    public function availableFor(Student $student): Collection
    {
        return $this->exams->availableForStudent($student->id);
    }

    /**
     * Prova para o aluno responder (sem expor o gabarito).
     */
    public function examForStudent(Exam $exam): Exam
    {
        return $this->exams->loadQuestions($exam);
    }

    /**
     * Registra a tentativa do aluno e faz a correção automática.
     *
     * @param  array<int, array{question_id: int, option_id: int}>  $answers
     *
     * @throws ExamAlreadyAttemptedException
     * @throws ValidationException
     */
    public function submit(Exam $exam, Student $student, array $answers): ExamAttempt
    {
        if ($this->attempts->existsForStudentAndExam($student->id, $exam->id)) {
            throw new ExamAlreadyAttemptedException;
        }

        $exam->load('questions.options');
        $questions = $exam->questions;

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'exam' => 'Esta prova não possui questões e não pode ser respondida.',
            ]);
        }

        $answersByQuestion = collect($answers)->keyBy('question_id');

        $score = 0;
        $rows = [];

        foreach ($questions as $question) {
            if (! $answersByQuestion->has($question->id)) {
                throw ValidationException::withMessages([
                    'answers' => 'Todas as questões da prova devem ser respondidas.',
                ]);
            }

            $chosenOptionId = (int) $answersByQuestion[$question->id]['option_id'];
            $chosenOption = $question->options->firstWhere('id', $chosenOptionId);

            if (! $chosenOption) {
                throw ValidationException::withMessages([
                    'answers' => "A alternativa informada não pertence à questão {$question->id}.",
                ]);
            }

            $isCorrect = (bool) $chosenOption->is_correct;
            if ($isCorrect) {
                $score++;
            }

            $rows[] = [
                'question_id' => $question->id,
                'option_id' => $chosenOptionId,
                'is_correct' => $isCorrect,
            ];
        }

        $total = $questions->count();
        $percentage = round(($score / $total) * 100, 2);

        try {
            $attempt = DB::transaction(function () use ($exam, $student, $score, $total, $percentage, $rows): ExamAttempt {
                // Trava a linha da prova: serializa com uma edição concorrente
                // (ver ExamService::update), que apagaria as questões.
                Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

                return $this->attempts->createWithAnswers($exam, $student, $score, $total, $percentage, $rows);
            });
        } catch (UniqueConstraintViolationException) {
            // Envio concorrente passou pela verificação acima; o índice único
            // (exam_id, student_id) é a garantia final → mesmo 409 do caso comum.
            throw new ExamAlreadyAttemptedException;
        }

        // Uma nova tentativa muda as métricas → invalida o cache do dashboard.
        $this->dashboard->flushCache();

        return $this->attempts->loadResult($attempt);
    }

    /**
     * Resultado detalhado de uma tentativa.
     */
    public function result(ExamAttempt $attempt): ExamAttempt
    {
        return $this->attempts->loadResult($attempt);
    }
}
