<?php

namespace App\Repositories\Eloquent;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function averagePercentage(int $teacherId): float
    {
        return round((float) $this->attemptsOf($teacherId)->avg('percentage'), 2);
    }

    public function totalAttempts(int $teacherId): int
    {
        return $this->attemptsOf($teacherId)->count();
    }

    public function bestAttempt(int $teacherId): ?ExamAttempt
    {
        return $this->rankedQuery($teacherId)->with(['student', 'exam'])->first();
    }

    public function worstAttempt(int $teacherId): ?ExamAttempt
    {
        return $this->attemptsOf($teacherId)
            ->with(['student', 'exam'])
            ->orderBy('percentage')
            ->orderBy('score')
            ->orderByDesc('submitted_at')
            ->first();
    }

    public function ranking(int $teacherId, ?int $examId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->rankedQuery($teacherId)
            ->when($examId, fn (Builder $q) => $q->where('exam_id', $examId))
            ->with(['student', 'exam'])
            ->paginate(perPage: $perPage, page: $page);
    }

    public function examMetrics(int $teacherId): Collection
    {
        return Exam::query()
            ->where('teacher_id', $teacherId)
            ->withCount('attempts')
            ->withAvg('attempts', 'percentage')
            ->withMax('attempts', 'percentage')
            ->withMin('attempts', 'percentage')
            ->orderByRaw('LOWER(title)')
            ->orderBy('id')
            ->get();
    }

    public function studentAverages(int $teacherId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->attemptsOf($teacherId)
            ->join('students', 'students.id', '=', 'exam_attempts.student_id')
            ->groupBy('exam_attempts.student_id', 'students.name')
            ->select([
                'exam_attempts.student_id',
                'students.name as student_name',
            ])
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('AVG(exam_attempts.percentage) as average_percentage')
            ->orderByDesc('average_percentage')
            ->orderByRaw('LOWER(students.name)')
            ->orderBy('exam_attempts.student_id')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Tentativas feitas nas provas do professor.
     *
     * @return Builder<ExamAttempt>
     */
    private function attemptsOf(int $teacherId): Builder
    {
        return ExamAttempt::query()->whereIn(
            'exam_attempts.exam_id',
            Exam::query()->select('id')->where('teacher_id', $teacherId),
        );
    }

    /**
     * Ordenação canônica do ranking: maior percentual, depois maior acerto,
     * e em caso de empate quem enviou primeiro fica na frente.
     *
     * @return Builder<ExamAttempt>
     */
    private function rankedQuery(int $teacherId): Builder
    {
        return $this->attemptsOf($teacherId)
            ->orderByDesc('percentage')
            ->orderByDesc('score')
            ->orderBy('submitted_at');
    }
}
