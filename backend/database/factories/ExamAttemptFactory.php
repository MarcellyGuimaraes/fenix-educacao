<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(1, 10);
        $score = fake()->numberBetween(0, $total);

        return [
            'exam_id' => Exam::factory(),
            'student_id' => Student::factory(),
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $total > 0 ? round(($score / $total) * 100, 2) : 0,
            'submitted_at' => now(),
        ];
    }
}
