<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    #[OA\Get(
        path: '/dashboard/summary',
        tags: ['Dashboard (Professor)'],
        summary: 'Métricas gerais: média, melhor (Top 1), pior e total',
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
    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->summary()]);
    }

    #[OA\Get(
        path: '/dashboard/ranking',
        tags: ['Dashboard (Professor)'],
        summary: 'Ranking de tentativas, ordenado por desempenho e paginado',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RankingItem')),
                new OA\Property(property: 'meta', type: 'object', properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                ]),
            ])),
        ]
    )]
    public function ranking(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        return response()->json($this->dashboard->ranking($perPage, $page));
    }
}
