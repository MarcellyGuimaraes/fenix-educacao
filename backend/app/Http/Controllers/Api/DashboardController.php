<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RankingResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->summary()]);
    }

    public function ranking(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 50));
        $page = max(1, (int) $request->integer('page', 1));

        $paginator = $this->dashboard->ranking($perPage, $page);

        // Injeta a posição no ranking a partir do offset da página atual.
        $start = $paginator->firstItem() ?? 0;
        $paginator->getCollection()->each(function ($attempt, $index) use ($start): void {
            $attempt->position = $start + $index;
        });

        return RankingResource::collection($paginator);
    }
}
