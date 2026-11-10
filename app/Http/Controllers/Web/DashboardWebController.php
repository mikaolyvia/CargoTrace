<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardWebController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Render interactive operational analytics dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $metrics = $this->dashboardService->getMetrics($user);

        return view('dashboard.index', [
            'metrics' => $metrics,
            'user' => $user,
        ]);
    }
}
