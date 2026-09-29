<?php

namespace App\Repositories\Contracts;

use App\Models\ExamAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardRepositoryInterface
{
    public function averagePercentage(): float;

    public function totalAttempts(): int;

    /**
     * Tentativa com melhor desempenho (Top 1).
     */
    public function bestAttempt(): ?ExamAttempt;

    /**
     * Tentativa com pior desempenho.
     */
    public function worstAttempt(): ?ExamAttempt;

    /**
     * Ranking de tentativas ordenado por desempenho, paginado.
     *
     * @return LengthAwarePaginator<int, ExamAttempt>
     */
    public function ranking(int $perPage, int $page): LengthAwarePaginator;
}
