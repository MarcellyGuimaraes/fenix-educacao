<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Teacher;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Dashboard do professor: todas as métricas consideram apenas as tentativas
 * feitas nas provas do professor identificado nos headers.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/dashboard/summary',
        tags: ['Dashboard (Professor)'],
        summary: 'Métricas gerais das provas do professor: média, melhor (Top 1), pior e total',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/DashboardSummary'),
            ])),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->summary($this->teacher($request))]);
    }

    #[OA\Get(
        path: '/dashboard/exams',
        tags: ['Dashboard (Professor)'],
        summary: 'Métricas por prova (média, melhor, pior e total), inclusive provas sem tentativas',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ExamMetric')),
            ])),
        ]
    )]
    public function exams(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->examMetrics($this->teacher($request))]);
    }

    #[OA\Get(
        path: '/dashboard/students',
        tags: ['Dashboard (Professor)'],
        summary: 'Aluno × média: média de cada aluno e diferença para a média geral, paginado',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentAverage')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
        ]
    )]
    public function students(Request $request): JsonResponse
    {
        [$perPage, $page] = $this->pagination($request);

        return response()->json($this->dashboard->studentAverages($this->teacher($request), $perPage, $page));
    }

    #[OA\Get(
        path: '/dashboard/ranking',
        tags: ['Dashboard (Professor)'],
        summary: 'Ranking de tentativas, ordenado por desempenho, paginado e filtrável por prova',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam_id', in: 'query', required: false, description: 'Filtra o ranking por uma prova do professor', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RankingItem')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 403, description: 'Prova de outro professor', content: new OA\JsonContent(ref: '#/components/schemas/Message')),
            new OA\Response(response: 404, description: 'Prova não encontrada'),
            new OA\Response(response: 422, description: 'exam_id inválido', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function ranking(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        $validated = $request->validate([
            'exam_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $examId = isset($validated['exam_id']) ? (int) $validated['exam_id'] : null;

        if ($examId !== null) {
            $exam = Exam::query()->findOrFail($examId);
            abort_unless($exam->isOwnedBy($teacher), 403, Exam::NOT_OWNER_MESSAGE);
        }

        [$perPage, $page] = $this->pagination($request);

        return response()->json($this->dashboard->ranking($teacher, $examId, $perPage, $page));
    }

    private function teacher(Request $request): Teacher
    {
        return $request->attributes->get('teacher');
    }

    /**
     * @return array{int, int}
     */
    private function pagination(Request $request): array
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        return [$perPage, $page];
    }
}
