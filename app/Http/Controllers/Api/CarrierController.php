<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarrierController extends Controller
{
    /**
     * Display a listing of carriers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Carrier::query();

        // Default to active carriers unless explicitly asked for all
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->active();
        }

        if ($request->filled('search')) {
            $search = addcslashes($request->input('search'), '%_');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("name LIKE ? ESCAPE '\\'", ["%{$search}%"])
                  ->orWhereRaw("code LIKE ? ESCAPE '\\'", ["%{$search}%"]);
            });
        }

        $perPage = min(100, max(1, $request->integer('per_page', 15)));
        $carriers = $query->orderBy('name')->paginate($perPage);

        return CarrierResource::collection($carriers);
    }
}
