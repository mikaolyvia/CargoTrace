<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShipmentRequest;
use App\Http\Requests\Api\TransitionStatusRequest;
use App\Http\Requests\Api\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * Display a listing of shipments with server-side filtering, customer scoping, and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Shipment::class);

        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination', 'currentLocation'])
            ->latest('id');

        // Customer scoping (Anti-IDOR): Customers only see their own shipments
        if ($request->user()->isCustomer()) {
            $query->where('customer_id', $request->user()->id);
        } elseif ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('current_status', strtoupper($request->input('status')));
        } elseif ($request->filled('current_status')) {
            $query->where('current_status', strtoupper($request->input('current_status')));
        }

        // Carrier filter
        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->integer('carrier_id'));
        }

        // Cargo type filter
        if ($request->filled('cargo_type')) {
            $query->where('cargo_type', strtoupper($request->input('cargo_type')));
        }

        // Delay filter
        if ($request->has('is_delayed')) {
            $query->where('is_delayed', filter_var($request->input('is_delayed'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by shipment number (escape LIKE wildcards % and _)
        if ($request->filled('search')) {
            $search = addcslashes($request->input('search'), '%_');
            $query->whereRaw("shipment_number LIKE ? ESCAPE '\\'", ["%{$search}%"]);
        }

        $perPage = min(100, max(1, $request->integer('per_page', 15)));
        $shipments = $query->paginate($perPage);

        return ShipmentResource::collection($shipments);
    }

    /**
     * Store a newly created shipment.
     */
    public function store(StoreShipmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Shipment::class);

        $shipment = $this->shipmentService->createShipment(
            $request->validated(),
            $request->user()
        );

        $shipment->load(['customer', 'carrier', 'origin', 'destination', 'currentLocation', 'items', 'trackingEvents']);

        return (new ShipmentResource($shipment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified shipment.
     */
    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        $shipment->load(['customer', 'carrier', 'origin', 'destination', 'currentLocation', 'items']);

        return new ShipmentResource($shipment);
    }

    /**
     * Update the specified shipment metadata (status transitions prohibited here).
     */
    public function update(UpdateShipmentRequest $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('update', $shipment);

        $shipment->update($request->validated());

        return new ShipmentResource($shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'currentLocation']));
    }

    /**
     * Execute a validated macro status transition via ShipmentService engine.
     */
    public function updateStatus(TransitionStatusRequest $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('transition', $shipment);

        try {
            $updated = $this->shipmentService->transitionTo(
                $shipment,
                $request->input('status'),
                $request->validated(),
                $request->user()
            );

            return (new ShipmentResource($updated->load(['customer', 'carrier', 'origin', 'destination', 'currentLocation', 'trackingEvents'])))
                ->response()
                ->setStatusCode(200);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Soft-delete the specified shipment (restricted to Admin).
     */
    public function destroy(Shipment $shipment): JsonResponse
    {
        Gate::authorize('delete', $shipment);

        $shipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipment successfully deleted.',
        ], 200);
    }
}
