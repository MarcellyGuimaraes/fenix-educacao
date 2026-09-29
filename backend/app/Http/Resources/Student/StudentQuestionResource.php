<?php

namespace App\Http\Resources\Student;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Question
 */
class StudentQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statement' => $this->statement,
            'order' => $this->order,
            'options' => StudentOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
