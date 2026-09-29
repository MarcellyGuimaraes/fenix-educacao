<?php

namespace App\Http\Resources\Student;

use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alternativa na visão do aluno: nunca expõe qual é a correta.
 *
 * @mixin Option
 */
class StudentOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'order' => $this->order,
        ];
    }
}
