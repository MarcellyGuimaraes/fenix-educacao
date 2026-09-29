<?php

namespace App\Repositories\Eloquent;

use App\Models\ExamAttempt;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function averagePercentage(): float
    {
        return round((float) ExamAttempt::query()->avg('percentage'), 2);
    }

    public function totalAttempts(): int
    {
        return ExamAttempt::query()->count();
    }

    public function bestAttempt(): ?ExamAttempt
    {
        return $this->rankedQuery()->with(['student', 'exam'])->first();
    }

    public function worstAttempt(): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->with(['student', 'exam'])
            ->orderBy('percentage')
            ->orderBy('score')
            ->orderByDesc('submitted_at')
            ->first();
    }

    public function ranking(int $perPage, int $page): LengthAwarePaginator
    {
        return $this->rankedQuery()
            ->with(['student', 'exam'])
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Ordenação canônica do ranking: maior percentual, depois maior acerto,
     * e em caso de empate quem enviou primeiro fica na frente.
     */
    private function rankedQuery()
    {
        return ExamAttempt::query()
            ->orderByDesc('percentage')
            ->orderByDesc('score')
            ->orderBy('submitted_at');
    }
}
