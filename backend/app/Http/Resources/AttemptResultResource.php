<?php

namespace App\Http\Resources;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resultado detalhado de uma tentativa: pontuação, percentual e o detalhamento
 * questão a questão (alternativa escolhida x correta).
 *
 * @mixin ExamAttempt
 */
class AttemptResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'exam_title' => $this->whenLoaded('exam', fn () => $this->exam->title),
            'student_name' => $this->whenLoaded('student', fn () => $this->student->name),
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => (float) $this->percentage,
            'submitted_at' => $this->submitted_at,
            'answers' => $this->whenLoaded('answers', fn () => $this->answers->map(function ($answer) {
                $correctOption = $answer->question?->correctOption;

                return [
                    'question_id' => $answer->question_id,
                    'statement' => $answer->question?->statement,
                    'chosen_option_id' => $answer->option_id,
                    'chosen_option_text' => $answer->option?->text,
                    'is_correct' => (bool) $answer->is_correct,
                    'correct_option_id' => $correctOption?->id,
                    'correct_option_text' => $correctOption?->text,
                ];
            })),
        ];
    }
}
