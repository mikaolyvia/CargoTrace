<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Retrieve aggregated operational dashboard metrics.
     * Accessible by authenticated ADMIN, OPERATOR, and CUSTOMER (scoped to own data).
     */
    public function index(Request $request): JsonResponse
    {
        $metrics = $this->dashboardService->getMetrics($request->user());

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ], 200);
    }
}
