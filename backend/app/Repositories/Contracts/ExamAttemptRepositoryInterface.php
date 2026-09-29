<?php

namespace App\Repositories\Contracts;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;

interface ExamAttemptRepositoryInterface
{
    public function existsForStudentAndExam(int $studentId, int $examId): bool;

    /**
     * Tentativa do aluno em uma prova, se existir.
     */
    public function findForStudentAndExam(int $studentId, int $examId): ?ExamAttempt;

    /**
     * Persiste uma tentativa e suas respostas.
     *
     * @param  array<int, array{question_id: int, option_id: int, is_correct: bool}>  $answers
     */
    public function createWithAnswers(Exam $exam, Student $student, int $score, int $total, float $percentage, array $answers): ExamAttempt;

    /**
     * Carrega o resultado detalhado de uma tentativa.
     */
    public function loadResult(ExamAttempt $attempt): ExamAttempt;
}
