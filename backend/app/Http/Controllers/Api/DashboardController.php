<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->summary()]);
    }

    public function ranking(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        return response()->json($this->dashboard->ranking($perPage, $page));
    }
}
