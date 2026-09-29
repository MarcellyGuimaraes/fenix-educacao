<?php

namespace App\Http\Resources\Student;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Prova para o aluno responder (questões e alternativas, sem gabarito).
 *
 * @mixin Exam
 */
class StudentExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'questions' => StudentQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
