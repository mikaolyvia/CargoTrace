<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTrackingEventRequest;
use App\Http\Resources\TrackingEventResource;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class TrackingController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Retrieve chronological tracking timeline and Leaflet map route points for a shipment.
     */
    public function index(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $order = $request->query('order', 'asc');
        $timeline = $this->trackingService->getTimeline($shipment, $order);
        $routePoints = $this->trackingService->getMapRoutePoints($shipment);

        return response()->json([
            'success' => true,
            'data' => [
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'current_status' => $shipment->current_status,
                'route_points' => $routePoints,
                'timeline' => TrackingEventResource::collection($timeline),
            ],
        ], 200);
    }

    /**
     * Append a new tracking event (maintaining append-only immutability).
     */
    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('addTrackingEvent', $shipment);

        try {
            $eventType = strtoupper($request->input('event_type'));
            $intermediateTypes = ['IN_TRANSIT', 'ARRIVED', 'EXCEPTION'];

            if (in_array($eventType, $intermediateTypes, true)) {
                $event = $this->trackingService->recordIntermediateEvent(
                    $shipment,
                    $eventType,
                    $request->validated(),
                    $request->user()
                );
            } else {
                $event = $this->trackingService->recordEvent(
                    $shipment,
                    $request->validated(),
                    $request->user()
                );
            }

            return (new TrackingEventResource($event->load(['location', 'creator'])))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
