<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;
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
     * Ranking de tentativas paginado, já como estrutura serializável
     * (linhas + metadados de paginação). Cacheamos o DTO, nunca o objeto
     * paginador — que não sobrevive à (des)serialização no cache.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function ranking(int $perPage, int $page): array
    {
        return $this->remember("ranking:{$perPage}:{$page}", function () use ($perPage, $page): array {
            $paginator = $this->dashboard->ranking($perPage, $page);
            $start = $paginator->firstItem() ?? 0;

            $rows = [];
            foreach ($paginator->items() as $index => $attempt) {
                $rows[] = [
                    'position' => $start + $index,
                    'attempt_id' => $attempt->id,
                    'student_name' => $attempt->student->name,
                    'exam_title' => $attempt->exam->title,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'percentage' => (float) $attempt->percentage,
                ];
            }

            return [
                'data' => $rows,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        });
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
