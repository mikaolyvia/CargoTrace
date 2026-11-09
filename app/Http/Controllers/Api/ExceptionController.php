<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportExceptionRequest;
use App\Http\Requests\Api\ResolveExceptionRequest;
use App\Http\Resources\ShipmentExceptionResource;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    /**
     * List exceptions for a specific shipment.
     */
    public function index(Request $request, Shipment $shipment): AnonymousResourceCollection
    {
        Gate::authorize('view', $shipment);

        $perPage = min(100, max(1, $request->integer('per_page', 15)));
        $exceptions = $shipment->exceptions()
            ->with(['reporter', 'resolver', 'trackingEvent.location'])
            ->latest('id')
            ->paginate($perPage);

        return ShipmentExceptionResource::collection($exceptions);
    }

    /**
     * Report an operational exception for a shipment.
     */
    public function store(ReportExceptionRequest $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('reportException', $shipment);

        try {
            $exception = $this->exceptionService->reportException(
                $shipment,
                $request->validated(),
                $request->user()
            );

            return (new ShipmentExceptionResource($exception))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Transition exception status to INVESTIGATING.
     */
    public function investigate(Request $request, ShipmentException $exception): JsonResponse|ShipmentExceptionResource
    {
        Gate::authorize('investigate', $exception);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updated = $this->exceptionService->investigateException(
                $exception,
                $request->user(),
                $request->input('notes')
            );

            $updated->load(['shipment', 'reporter', 'resolver', 'trackingEvent']);

            return new ShipmentExceptionResource($updated);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve an exception with resolution notes and optional operator ETA correction.
     */
    public function resolve(ResolveExceptionRequest $request, ShipmentException $exception): ShipmentExceptionResource
    {
        Gate::authorize('resolve', $exception);

        $resolved = $this->exceptionService->resolveException(
            $exception,
            $request->validated(),
            $request->user()
        );

        $resolved->load(['shipment', 'reporter', 'resolver', 'trackingEvent']);

        return new ShipmentExceptionResource($resolved);
    }
}
