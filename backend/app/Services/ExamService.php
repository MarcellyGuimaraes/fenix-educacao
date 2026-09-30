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
     * Provas do professor (o professor só vê as próprias provas).
     *
     * @return Collection<int, Exam>
     */
    public function list(Teacher $teacher): Collection
    {
        return $this->exams->forTeacherWithCounts($teacher->id);
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

        $exam = DB::transaction(fn (): Exam => $this->exams->create($data));

        // O dashboard lista também provas sem tentativas → invalida o cache.
        $this->dashboard->flushCache();

        return $exam;
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
        $exam = DB::transaction(function () use ($exam, $data): Exam {
            // Trava a linha da prova: uma submissão concorrente espera o fim
            // desta transação (ver ExamAttemptService::submit).
            Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

            if ($exam->attempts()->exists()) {
                throw ExamHasAttemptsException::forUpdate();
            }

            return $this->exams->update($exam, $data);
        });

        // O título aparece nas métricas e no ranking → invalida o cache.
        $this->dashboard->flushCache();

        return $exam;
    }

    /**
     * Exclui uma prova sem tentativas. Provas já respondidas não podem ser
     * excluídas, pela mesma razão da edição: o histórico de tentativas precisa
     * ser preservado (o banco também impede, via FK restritiva).
     *
     * @throws ExamHasAttemptsException
     */
    public function delete(Exam $exam): void
    {
        DB::transaction(function () use ($exam): void {
            // Mesma trava da edição e da submissão (ver ExamAttemptService::submit).
            Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

            if ($exam->attempts()->exists()) {
                throw ExamHasAttemptsException::forDelete();
            }

            $this->exams->delete($exam);
        });

        // A prova sai das métricas por prova → invalida o cache do dashboard.
        $this->dashboard->flushCache();
    }
}
