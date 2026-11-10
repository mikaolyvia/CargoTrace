<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    /**
     * List ocean cargo vessels with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vessel::with('carrier:id,name,code');

        if ($request->filled('search')) {
            $search = addcslashes($request->query('search'), '%_');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('imo_number', 'LIKE', "%{$search}%")
                  ->orWhere('mmsi', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->query('carrier_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $vessels = $query->orderBy('name')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $vessels,
        ], 200);
    }

    /**
     * Retrieve single vessel details.
     */
    public function show(Vessel $vessel): JsonResponse
    {
        $vessel->load(['carrier', 'positions' => function ($q) {
            $q->orderByDesc('recorded_at')->limit(20);
        }]);

        return response()->json([
            'success' => true,
            'data' => $vessel,
        ], 200);
    }

    /**
     * Retrieve chronological historical AIS positions for a vessel.
     */
    public function positions(Vessel $vessel): JsonResponse
    {
        $positions = $vessel->positions()
            ->orderBy('recorded_at', 'asc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'vessel' => [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'imo_number' => $vessel->imo_number,
                    'flag_country' => $vessel->flag_country,
                ],
                'positions' => $positions,
            ],
        ], 200);
    }
}
