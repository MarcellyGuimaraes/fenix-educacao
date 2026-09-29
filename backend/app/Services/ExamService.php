<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Teacher;
use App\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        private readonly ExamRepositoryInterface $exams,
    ) {}

    /**
     * @return Collection<int, Exam>
     */
    public function list(): Collection
    {
        return $this->exams->allWithCounts();
    }

    public function find(Exam $exam): Exam
    {
        return $this->exams->loadQuestions($exam);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Teacher $teacher, array $data): Exam
    {
        $data['teacher_id'] = $teacher->id;

        return DB::transaction(fn (): Exam => $this->exams->create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(fn (): Exam => $this->exams->update($exam, $data));
    }

    public function delete(Exam $exam): void
    {
        $this->exams->delete($exam);
    }
}
