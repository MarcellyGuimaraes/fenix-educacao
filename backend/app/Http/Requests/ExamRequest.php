<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorização de perfil é feita pelo middleware `profile:teacher`.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.statement' => ['required', 'string'],

            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.text' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    /**
     * Regra de negócio: cada questão deve ter exatamente uma alternativa correta.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('questions', []) as $index => $question) {
                $correctCount = collect($question['options'] ?? [])
                    ->filter(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->count();

                if ($correctCount !== 1) {
                    $validator->errors()->add(
                        "questions.{$index}.options",
                        'Cada questão deve ter exatamente uma alternativa correta.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'questions.required' => 'A prova deve ter ao menos uma questão.',
            'questions.*.options.min' => 'Cada questão deve ter ao menos duas alternativas.',
        ];
    }
}
