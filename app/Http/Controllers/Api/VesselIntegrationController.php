<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Models\VesselPosition;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VesselIntegrationController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Ingest automated AIS vessel position updates (from Python Data Collector).
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'imo_number' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_knots' => 'nullable|numeric|min:0',
            'heading_degrees' => 'nullable|numeric|between:0,360',
            'status' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:50',
            'recorded_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data posisi kapal gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imo = $request->input('imo_number');
        $vessel = Vessel::where('imo_number', $imo)->first();

        if (! $vessel) {
            return response()->json([
                'success' => false,
                'message' => "Kapal dengan nomor IMO [{$imo}] tidak ditemukan dalam sistem.",
            ], 404);
        }

        $lat = round((float) $request->input('latitude'), 7);
        $lng = round((float) $request->input('longitude'), 7);
        $speed = $request->has('speed_knots') ? (float) $request->input('speed_knots') : null;
        $heading = $request->has('heading_degrees') ? (float) $request->input('heading_degrees') : null;
        $status = $request->input('status', 'UNDERWAY');
        $source = $request->input('source', 'AIS_PYTHON_COLLECTOR');
        $recordedAt = $request->filled('recorded_at') ? Carbon::parse($request->input('recorded_at')) : now();

        // Anti-duplicate position check: skip if exact coordinates match last position within 5 minutes
        if ($vessel->last_latitude !== null && $vessel->last_longitude !== null) {
            $isSameLocation = abs($vessel->last_latitude - $lat) < 0.0001 && abs($vessel->last_longitude - $lng) < 0.0001;
            $isRecent = $vessel->last_position_at && $recordedAt->diffInMinutes($vessel->last_position_at) < 5;

            if ($isSameLocation && $isRecent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Posisi kapal sama dengan data sebelumnya dalam interval singkat. Duplikasi diabaikan.',
                    'data' => [
                        'vessel_id' => $vessel->id,
                        'imo_number' => $vessel->imo_number,
                        'is_duplicate' => true,
                    ],
                ], 200);
            }
        }

        return DB::transaction(function () use ($vessel, $lat, $lng, $speed, $heading, $status, $source, $recordedAt, $request) {
            // 1. Create Position Record
            $position = VesselPosition::create([
                'vessel_id' => $vessel->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed_knots' => $speed,
                'heading_degrees' => $heading,
                'status' => $status,
                'source' => $source,
                'recorded_at' => $recordedAt,
            ]);

            // 2. Update Vessel Last Coordinates
            $vessel->update([
                'last_latitude' => $lat,
                'last_longitude' => $lng,
                'last_speed_knots' => $speed,
                'last_heading_degrees' => $heading,
                'status' => $status,
                'last_position_at' => $recordedAt,
            ]);

            // 3. Propagate to active IN_TRANSIT shipments assigned to this vessel
            $activeShipments = $vessel->shipments()
                ->where('current_status', 'IN_TRANSIT')
                ->get();

            $propagatedCount = 0;
            foreach ($activeShipments as $shipment) {
                $this->trackingService->recordIntermediateEvent($shipment, 'IN_TRANSIT', [
                    'event_time' => $recordedAt,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'description' => "AIS Auto-Update: Vessel {$vessel->name} underway at speed {$speed} kts, heading {$heading}°.",
                ], $request->user());
                $propagatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Posisi kapal berhasil diperbarui secara otomatis.',
                'data' => [
                    'position_id' => $position->id,
                    'vessel_id' => $vessel->id,
                    'vessel_name' => $vessel->name,
                    'imo_number' => $vessel->imo_number,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed_knots' => $speed,
                    'heading_degrees' => $heading,
                    'recorded_at' => $recordedAt->toIso8601String(),
                    'propagated_shipments' => $propagatedCount,
                ],
            ], 201);
        });
    }
}
