<?php

namespace App\Http\Resources;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Linha do ranking. A posição (`position`) é injetada pelo controller a partir
 * do offset da paginação.
 *
 * @mixin ExamAttempt
 */
class RankingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'position' => $this->position,
            'attempt_id' => $this->id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student->name),
            'exam_title' => $this->whenLoaded('exam', fn () => $this->exam->title),
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => (float) $this->percentage,
        ];
    }
}
