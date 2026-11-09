<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    /**
     * Display a listing of locations (ports, airports, hubs, warehouses).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Location::query();

        if ($request->filled('type')) {
            $query->ofType(strtoupper($request->input('type')));
        }

        if ($request->filled('country')) {
            $query->where('country', strtoupper($request->input('country')));
        }

        if ($request->filled('search')) {
            $search = addcslashes($request->input('search'), '%_');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("name LIKE ? ESCAPE '\\'", ["%{$search}%"])
                  ->orWhereRaw("code LIKE ? ESCAPE '\\'", ["%{$search}%"])
                  ->orWhereRaw("city LIKE ? ESCAPE '\\'", ["%{$search}%"]);
            });
        }

        $perPage = min(100, max(1, $request->integer('per_page', 15)));
        $locations = $query->orderBy('name')->paginate($perPage);

        return LocationResource::collection($locations);
    }
}
