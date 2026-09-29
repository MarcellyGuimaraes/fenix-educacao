<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Tag de cache usada para agrupar/invalidar os dados do dashboard.
     */
    private const CACHE_TAG = 'dashboard';

    private const TTL_SECONDS = 300;

    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
    ) {}

    /**
     * Métricas gerais: média, melhor (Top 1), pior e total de tentativas.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->remember('summary', function (): array {
            $best = $this->dashboard->bestAttempt();
            $worst = $this->dashboard->worstAttempt();

            return [
                'average_percentage' => $this->dashboard->averagePercentage(),
                'total_attempts' => $this->dashboard->totalAttempts(),
                'best' => $best ? [
                    'student_name' => $best->student->name,
                    'exam_title' => $best->exam->title,
                    'percentage' => (float) $best->percentage,
                ] : null,
                'worst' => $worst ? [
                    'student_name' => $worst->student->name,
                    'exam_title' => $worst->exam->title,
                    'percentage' => (float) $worst->percentage,
                ] : null,
            ];
        });
    }

    /**
     * Ranking de tentativas paginado.
     *
     * @return LengthAwarePaginator<int, \App\Models\ExamAttempt>
     */
    public function ranking(int $perPage, int $page): LengthAwarePaginator
    {
        return $this->remember("ranking:{$perPage}:{$page}", fn (): LengthAwarePaginator => $this->dashboard->ranking($perPage, $page));
    }

    /**
     * Invalida todo o cache do dashboard. Chamado quando uma nova tentativa
     * é registrada, para que as métricas nunca fiquem desatualizadas.
     */
    public function flushCache(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    /**
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function remember(string $key, \Closure $callback): mixed
    {
        return Cache::tags(self::CACHE_TAG)->remember("dashboard:{$key}", self::TTL_SECONDS, $callback);
    }
}
