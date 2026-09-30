<?php

namespace App\Services;

use App\Models\Teacher;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Métricas do dashboard do professor — sempre restritas às provas dele.
 */
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
     * Métricas gerais: média das provas (cada prova com o mesmo peso), média
     * por tentativa, melhor (Top 1), pior e total de tentativas.
     *
     * @return array<string, mixed>
     */
    public function summary(Teacher $teacher): array
    {
        return $this->remember($teacher, 'summary', function () use ($teacher): array {
            $best = $this->dashboard->bestAttempt($teacher->id);
            $worst = $this->dashboard->worstAttempt($teacher->id);

            return [
                'exams_average_percentage' => $this->dashboard->examsAveragePercentage($teacher->id),
                'average_percentage' => $this->dashboard->averagePercentage($teacher->id),
                'total_attempts' => $this->dashboard->totalAttempts($teacher->id),
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
     * Métricas por prova (inclusive provas ainda sem tentativas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function examMetrics(Teacher $teacher): array
    {
        return $this->remember($teacher, 'exams', function () use ($teacher): array {
            return $this->dashboard->examMetrics($teacher->id)
                ->map(fn ($exam): array => [
                    'exam_id' => $exam->id,
                    'exam_title' => $exam->title,
                    'attempts_count' => (int) $exam->attempts_count,
                    'average_percentage' => $this->percentage($exam->attempts_avg_percentage),
                    'best_percentage' => $this->percentage($exam->attempts_max_percentage),
                    'worst_percentage' => $this->percentage($exam->attempts_min_percentage),
                ])
                ->all();
        });
    }

    /**
     * Aluno × média: média de cada aluno nas provas do professor e o desvio
     * médio (em pontos percentuais) em relação à média de cada prova que ele
     * fez. Comparar prova a prova evita que um aluno que fez só a prova mais
     * difícil apareça abaixo da média mesmo tendo ido melhor que a turma.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function studentAverages(Teacher $teacher, int $perPage, int $page): array
    {
        return $this->remember($teacher, "students:{$perPage}:{$page}", function () use ($teacher, $perPage, $page): array {
            $paginator = $this->dashboard->studentAverages($teacher->id, $perPage, $page);

            $rows = [];
            foreach ($paginator->items() as $row) {
                $rows[] = [
                    'student_id' => (int) $row->student_id,
                    'student_name' => $row->student_name,
                    'attempts_count' => (int) $row->attempts_count,
                    'average_percentage' => (float) $this->percentage($row->average_percentage),
                    'difference_from_exam_average' => (float) $this->percentage($row->difference_from_exam_average),
                ];
            }

            return ['data' => $rows, 'meta' => $this->meta($paginator)];
        });
    }

    /**
     * Ranking de tentativas paginado, já como estrutura serializável
     * (linhas + metadados de paginação). Cacheamos o DTO, nunca o objeto
     * paginador — que não sobrevive à (des)serialização no cache.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function ranking(Teacher $teacher, ?int $examId, int $perPage, int $page): array
    {
        $filter = $examId ?? 'all';

        return $this->remember($teacher, "ranking:{$filter}:{$perPage}:{$page}", function () use ($teacher, $examId, $perPage, $page): array {
            $paginator = $this->dashboard->ranking($teacher->id, $examId, $perPage, $page);
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

            return ['data' => $rows, 'meta' => $this->meta($paginator)];
        });
    }

    /**
     * Invalida todo o cache do dashboard (de todos os professores). Chamado
     * quando uma nova tentativa é registrada ou uma prova é excluída, para que
     * as métricas nunca fiquem desatualizadas.
     */
    public function flushCache(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    /**
     * Agregações do banco chegam como string (numeric no PostgreSQL) ou null.
     */
    private function percentage(mixed $value): ?float
    {
        // "+ 0.0" normaliza -0.0 (desvios que arredondam para zero) em 0.0.
        return $value === null ? null : round((float) $value, 2) + 0.0;
    }

    /**
     * @return array<string, int>
     */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * A chave inclui o professor: o cache de um nunca é servido a outro.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function remember(Teacher $teacher, string $key, \Closure $callback): mixed
    {
        return Cache::tags(self::CACHE_TAG)->remember("dashboard:{$teacher->id}:{$key}", self::TTL_SECONDS, $callback);
    }
}
