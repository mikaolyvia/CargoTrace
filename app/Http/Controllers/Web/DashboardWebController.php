<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Location;
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
        $filters = $request->only([
            'search',
            'status',
            'carrier_id',
            'origin_id',
            'destination_id',
            'delay_status',
            'sort_by',
            'sort_order',
        ]);

        $metrics = $this->dashboardService->getMetrics($user, $filters);

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $locations = Location::orderBy('name')->get(['id', 'name', 'code', 'country', 'type']);

        return view('dashboard.index', [
            'metrics' => $metrics,
            'user' => $user,
            'filters' => $filters,
            'carriers' => $carriers,
            'locations' => $locations,
        ]);
    }
}
