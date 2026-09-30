<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * DashboardService isolado: o repositório é um mock (sem banco) e o cache é o
 * store em memória. Testa a montagem das métricas e o comportamento do cache.
 */
class DashboardServiceTest extends TestCase
{
    /** @var DashboardRepositoryInterface&MockInterface */
    private MockInterface $repository;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DashboardRepositoryInterface::class);
        $this->service = new DashboardService($this->repository);
    }

    private function teacher(int $id = 1): Teacher
    {
        $teacher = new Teacher(['name' => "Professor {$id}"]);
        $teacher->id = $id;

        return $teacher;
    }

    private function attempt(int $id, string $student, string $exam, int $score, int $total, float $percentage): ExamAttempt
    {
        $attempt = new ExamAttempt(['score' => $score, 'total_questions' => $total, 'percentage' => $percentage]);
        $attempt->id = $id;
        $attempt->setRelation('student', new Student(['name' => $student]));
        $attempt->setRelation('exam', new Exam(['title' => $exam]));

        return $attempt;
    }

    public function test_summary_monta_metricas_com_melhor_e_pior_tentativa(): void
    {
        $this->repository->shouldReceive('examsAveragePercentage')->with(1)->andReturn(62.96);
        $this->repository->shouldReceive('averagePercentage')->with(1)->andReturn(66.67);
        $this->repository->shouldReceive('totalAttempts')->with(1)->andReturn(7);
        $this->repository->shouldReceive('bestAttempt')->with(1)->andReturn($this->attempt(1, 'Ana', 'Lógica', 3, 3, 100));
        $this->repository->shouldReceive('worstAttempt')->with(1)->andReturn($this->attempt(2, 'Bia', 'Gerais', 1, 3, 33.33));

        $summary = $this->service->summary($this->teacher());

        $this->assertSame(62.96, $summary['exams_average_percentage']);
        $this->assertSame(66.67, $summary['average_percentage']);
        $this->assertSame(7, $summary['total_attempts']);
        $this->assertSame(['student_name' => 'Ana', 'exam_title' => 'Lógica', 'percentage' => 100.0], $summary['best']);
        $this->assertSame(['student_name' => 'Bia', 'exam_title' => 'Gerais', 'percentage' => 33.33], $summary['worst']);
    }

    public function test_summary_sem_tentativas_retorna_melhor_e_pior_nulos(): void
    {
        $this->repository->shouldReceive('examsAveragePercentage')->andReturn(0.0);
        $this->repository->shouldReceive('averagePercentage')->andReturn(0.0);
        $this->repository->shouldReceive('totalAttempts')->andReturn(0);
        $this->repository->shouldReceive('bestAttempt')->andReturnNull();
        $this->repository->shouldReceive('worstAttempt')->andReturnNull();

        $summary = $this->service->summary($this->teacher());

        $this->assertSame(0, $summary['total_attempts']);
        $this->assertNull($summary['best']);
        $this->assertNull($summary['worst']);
    }

    public function test_metricas_por_prova_arredondam_e_preservam_nulos(): void
    {
        $withAttempts = (new Exam)->forceFill([
            'id' => 1, 'title' => 'Com tentativas', 'attempts_count' => '3',
            'attempts_avg_percentage' => '66.666666', 'attempts_max_percentage' => '100.00', 'attempts_min_percentage' => '33.33',
        ]);
        $withoutAttempts = (new Exam)->forceFill([
            'id' => 2, 'title' => 'Sem tentativas', 'attempts_count' => 0,
            'attempts_avg_percentage' => null, 'attempts_max_percentage' => null, 'attempts_min_percentage' => null,
        ]);
        $this->repository->shouldReceive('examMetrics')->with(1)->andReturn(new Collection([$withAttempts, $withoutAttempts]));

        $metrics = $this->service->examMetrics($this->teacher());

        $this->assertSame([
            'exam_id' => 1, 'exam_title' => 'Com tentativas', 'attempts_count' => 3,
            'average_percentage' => 66.67, 'best_percentage' => 100.0, 'worst_percentage' => 33.33,
        ], $metrics[0]);
        $this->assertSame(0, $metrics[1]['attempts_count']);
        $this->assertNull($metrics[1]['average_percentage']);
        $this->assertNull($metrics[1]['best_percentage']);
        $this->assertNull($metrics[1]['worst_percentage']);
    }

    public function test_ranking_numera_posicoes_a_partir_da_pagina_atual(): void
    {
        // Página 2 com 5 por página: as posições começam em 6.
        $paginator = new LengthAwarePaginator(
            [$this->attempt(10, 'Ana', 'Lógica', 4, 5, 80), $this->attempt(11, 'Bia', 'Lógica', 3, 5, 60)],
            total: 7, perPage: 5, currentPage: 2,
        );
        $this->repository->shouldReceive('ranking')->with(1, null, 5, 2)->andReturn($paginator);

        $ranking = $this->service->ranking($this->teacher(), null, 5, 2);

        $this->assertSame([6, 7], array_column($ranking['data'], 'position'));
        $this->assertSame([
            'position' => 6, 'attempt_id' => 10, 'student_name' => 'Ana', 'exam_title' => 'Lógica',
            'score' => 4, 'total_questions' => 5, 'percentage' => 80.0,
        ], $ranking['data'][0]);
        $this->assertSame(['current_page' => 2, 'last_page' => 2, 'per_page' => 5, 'total' => 7], $ranking['meta']);
    }

    public function test_aluno_versus_media_converte_valores_do_banco(): void
    {
        $row = (object) [
            'student_id' => '5', 'student_name' => 'Ana', 'attempts_count' => '2',
            'average_percentage' => '72.5', 'difference_from_exam_average' => '-0.001',
        ];
        $this->repository->shouldReceive('studentAverages')->with(1, 10, 1)
            ->andReturn(new LengthAwarePaginator([$row], total: 1, perPage: 10, currentPage: 1));

        $result = $this->service->studentAverages($this->teacher(), 10, 1);

        $this->assertSame(5, $result['data'][0]['student_id']);
        $this->assertSame(2, $result['data'][0]['attempts_count']);
        $this->assertSame(72.5, $result['data'][0]['average_percentage']);
        // Desvio que arredonda para zero não deve aparecer como "-0".
        $this->assertSame('0', (string) $result['data'][0]['difference_from_exam_average']);
        $this->assertSame(1, $result['meta']['total']);
    }

    public function test_segunda_leitura_vem_do_cache(): void
    {
        $this->repository->shouldReceive('examMetrics')->once()->andReturn(new Collection);

        $this->service->examMetrics($this->teacher());
        $this->service->examMetrics($this->teacher());
    }

    public function test_cache_e_separado_por_professor(): void
    {
        $this->repository->shouldReceive('examMetrics')->with(1)->once()->andReturn(new Collection);
        $this->repository->shouldReceive('examMetrics')->with(2)->once()->andReturn(new Collection);

        $this->service->examMetrics($this->teacher(1));
        $this->service->examMetrics($this->teacher(2));
        $this->service->examMetrics($this->teacher(1));
    }

    public function test_flush_cache_forca_nova_consulta(): void
    {
        $this->repository->shouldReceive('examMetrics')->twice()->andReturn(new Collection);

        $this->service->examMetrics($this->teacher());
        $this->service->flushCache();
        $this->service->examMetrics($this->teacher());
    }
}
