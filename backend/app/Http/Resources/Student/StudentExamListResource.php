<?php

namespace App\Http\Resources\Student;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Item da lista de provas disponíveis para o aluno, com o status da tentativa
 * dele (a relação `attempts` vem pré-filtrada pelo aluno no repositório).
 *
 * @mixin Exam
 */
class StudentExamListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ExamAttempt|null $attempt */
        $attempt = $this->attempts->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'questions_count' => $this->questions_count,
            'attempted' => $attempt !== null,
            'attempt_id' => $attempt?->id,
            'percentage' => $attempt ? (float) $attempt->percentage : null,
        ];
    }
}
