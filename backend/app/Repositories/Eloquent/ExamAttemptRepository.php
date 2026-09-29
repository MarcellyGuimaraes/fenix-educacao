<?php

namespace App\Repositories\Eloquent;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Repositories\Contracts\ExamAttemptRepositoryInterface;

class ExamAttemptRepository implements ExamAttemptRepositoryInterface
{
    public function existsForStudentAndExam(int $studentId, int $examId): bool
    {
        return ExamAttempt::query()
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->exists();
    }

    public function findForStudentAndExam(int $studentId, int $examId): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->first();
    }

    public function createWithAnswers(Exam $exam, Student $student, int $score, int $total, float $percentage, array $answers): ExamAttempt
    {
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $percentage,
            'submitted_at' => now(),
        ]);

        $attempt->answers()->createMany($answers);

        return $attempt;
    }

    public function loadResult(ExamAttempt $attempt): ExamAttempt
    {
        return $attempt->load([
            'exam',
            'student',
            'answers.question',
            'answers.option',
            'answers.question.correctOption',
        ]);
    }
}
