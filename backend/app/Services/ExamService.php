<?php

namespace App\Services;

use App\Exceptions\ExamHasAttemptsException;
use App\Models\Exam;
use App\Models\Teacher;
use App\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        private readonly ExamRepositoryInterface $exams,
        private readonly DashboardService $dashboard,
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
        return $this->exams->loadQuestions($exam)->loadCount('attempts');
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
     * Atualiza a prova substituindo as questões. Provas já respondidas não
     * podem ser editadas: recriar as questões apagaria em cascata as
     * respostas dos alunos.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ExamHasAttemptsException
     */
    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data): Exam {
            // Trava a linha da prova: uma submissão concorrente espera o fim
            // desta transação (ver ExamAttemptService::submit).
            Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

            if ($exam->attempts()->exists()) {
                throw new ExamHasAttemptsException;
            }

            return $this->exams->update($exam, $data);
        });
    }

    public function delete(Exam $exam): void
    {
        $this->exams->delete($exam);

        // As tentativas da prova saem em cascata → invalida o cache do dashboard.
        $this->dashboard->flushCache();
    }
}
