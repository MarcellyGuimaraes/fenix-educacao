<?php

namespace App\Repositories\Contracts;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas do dashboard. Todas consideram apenas as tentativas feitas em
 * provas do professor informado.
 */
interface DashboardRepositoryInterface
{
    public function averagePercentage(int $teacherId): float;

    public function totalAttempts(int $teacherId): int;

    /**
     * Tentativa com melhor desempenho (Top 1).
     */
    public function bestAttempt(int $teacherId): ?ExamAttempt;

    /**
     * Tentativa com pior desempenho.
     */
    public function worstAttempt(int $teacherId): ?ExamAttempt;

    /**
     * Ranking de tentativas ordenado por desempenho, paginado e opcionalmente
     * filtrado por uma prova.
     *
     * @return LengthAwarePaginator<int, ExamAttempt>
     */
    public function ranking(int $teacherId, ?int $examId, int $perPage, int $page): LengthAwarePaginator;

    /**
     * Provas do professor (inclusive sem tentativas) com `attempts_count` e as
     * agregações `attempts_avg_percentage`, `attempts_max_percentage` e
     * `attempts_min_percentage`, ordenadas pelo título.
     *
     * @return Collection<int, Exam>
     */
    public function examMetrics(int $teacherId): Collection;

    /**
     * Média de cada aluno nas provas do professor, paginada. Cada linha traz
     * `student_id`, `student_name`, `attempts_count` e `average_percentage`.
     *
     * @return LengthAwarePaginator<int, ExamAttempt>
     */
    public function studentAverages(int $teacherId, int $perPage, int $page): LengthAwarePaginator;
}
