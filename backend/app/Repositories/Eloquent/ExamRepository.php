<?php

namespace App\Repositories\Eloquent;

use App\Models\Exam;
use App\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ExamRepository implements ExamRepositoryInterface
{
    public function allWithCounts(): Collection
    {
        return Exam::query()
            ->withCount(['questions', 'attempts'])
            ->latest()
            ->get();
    }

    public function availableForStudent(int $studentId): Collection
    {
        return Exam::query()
            ->withCount('questions')
            ->with(['attempts' => fn ($q) => $q->where('student_id', $studentId)])
            ->latest()
            ->get();
    }

    public function loadQuestions(Exam $exam): Exam
    {
        return $exam->load('questions.options');
    }

    public function create(array $data): Exam
    {
        $exam = Exam::create([
            'teacher_id' => $data['teacher_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncQuestions($exam, $data['questions']);

        return $exam->load('questions.options');
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        // Substitui completamente o conjunto de questões/alternativas.
        $exam->questions()->delete();
        $this->syncQuestions($exam, $data['questions']);

        return $exam->load('questions.options');
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    /**
     * Cria as questões e alternativas de uma prova.
     *
     * @param  array<int, array{statement: string, options: array<int, array{text: string, is_correct: bool}>}>  $questions
     */
    private function syncQuestions(Exam $exam, array $questions): void
    {
        foreach ($questions as $qIndex => $questionData) {
            $question = $exam->questions()->create([
                'statement' => $questionData['statement'],
                'order' => $qIndex + 1,
            ]);

            foreach ($questionData['options'] as $oIndex => $optionData) {
                $question->options()->create([
                    'text' => $optionData['text'],
                    'is_correct' => (bool) $optionData['is_correct'],
                    'order' => $oIndex + 1,
                ]);
            }
        }
    }
}
