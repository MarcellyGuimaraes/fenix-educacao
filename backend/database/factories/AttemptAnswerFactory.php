<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptAnswer>
 */
class AttemptAnswerFactory extends Factory
{
    protected $model = AttemptAnswer::class;

    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'question_id' => Question::factory(),
            'option_id' => Option::factory(),
            'is_correct' => fake()->boolean(),
        ];
    }
}
