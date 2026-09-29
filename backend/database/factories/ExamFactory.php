<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(10),
        ];
    }
}
