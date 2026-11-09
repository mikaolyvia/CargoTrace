<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExceptionService
{
    public function __construct(
        protected EtaService $etaService,
        protected TrackingService $trackingService
    ) {}

    /**
     * Report an operational exception on a shipment with transactional tracking event & delta ETA update.
     *
     * @param Shipment $shipment
     * @param array $data (exception_type, severity, description, impact_hours, detected_at, location_id)
     * @param User $reporter
     * @return ShipmentException
     */
    public function reportException(Shipment $shipment, array $data, User $reporter): ShipmentException
    {
        $type = strtoupper($data['exception_type'] ?? '');
        if (! in_array($type, ShipmentException::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid exception type [{$type}]. Allowed types: " . implode(', ', ShipmentException::VALID_TYPES));
        }

        $severity = strtoupper($data['severity'] ?? 'MEDIUM');
        $allowedSeverities = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
        if (! in_array($severity, $allowedSeverities, true)) {
            throw new InvalidArgumentException("Invalid severity [{$severity}]. Allowed: " . implode(', ', $allowedSeverities));
        }

        return DB::transaction(function () use ($shipment, $data, $type, $severity, $reporter) {
            $impactHours = max(0.00, (float) ($data['impact_hours'] ?? 0.00));
            $detectedAt = isset($data['detected_at']) ? Carbon::parse($data['detected_at']) : Carbon::now();

            // 1. Create exception record
            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'exception_type' => $type,
                'severity' => $severity,
                'status' => ShipmentException::STATUS_OPEN,
                'impact_hours' => $impactHours,
                'description' => $data['description'] ?? "Incident logged: {$type}",
                'detected_at' => $detectedAt,
                'reported_by' => $reporter->id,
            ]);

            // 2. Atomically record EXCEPTION tracking event
            $event = $this->trackingService->recordEvent($shipment, [
                'event_type' => 'EXCEPTION',
                'event_time' => $detectedAt,
                'location_id' => $data['location_id'] ?? $shipment->current_location_id,
                'description' => "EXCEPTION RECORDED [{$type}] ({$severity}): " . ($data['description'] ?? 'Operational incident reported.'),
            ], $reporter);

            $exception->update(['tracking_event_id' => $event->id]);

            // 3. Delta-based ETA adjustment (initial impact hours)
            if ($impactHours > 0) {
                $this->etaService->adjustEta($shipment, $impactHours);
            }

            return $exception->fresh(['shipment', 'trackingEvent', 'reporter']);
        });
    }

    /**
     * Update exception details and apply delta-based ETA adjustment to prevent double-counting.
     */
    public function updateException(ShipmentException $exception, array $data, User $editor): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data) {
            // Apply delta if impact_hours changed
            if (isset($data['impact_hours'])) {
                $newImpact = max(0.00, (float) $data['impact_hours']);
                $oldImpact = (float) $exception->impact_hours;
                $deltaHours = $newImpact - $oldImpact;

                if (abs($deltaHours) > 0.001) {
                    $this->etaService->adjustEta($exception->shipment, $deltaHours);
                }

                $exception->impact_hours = $newImpact;
            }

            if (isset($data['description'])) {
                $exception->description = $data['description'];
            }

            if (isset($data['severity'])) {
                $exception->severity = strtoupper($data['severity']);
            }

            $exception->save();

            return $exception->fresh();
        });
    }

    /**
     * Transition exception status from OPEN to INVESTIGATING.
     */
    public function investigateException(ShipmentException $exception, User $operator, ?string $notes = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $operator, $notes) {
            $exception->update([
                'status' => ShipmentException::STATUS_INVESTIGATING,
                'resolution_notes' => $notes ?? $exception->resolution_notes,
            ]);

            // Record checkpoint event acknowledging investigation
            $this->trackingService->recordIntermediateEvent($exception->shipment, 'EXCEPTION', [
                'description' => "EXCEPTION INVESTIGATION: Status updated to INVESTIGATING for [{$exception->exception_type}].",
            ], $operator);

            return $exception->fresh();
        });
    }

    /**
     * Resolve exception.
     * Note: Does NOT automatically roll back ETA. ETA is only adjusted if an explicit
     * eta_correction_hours delta is supplied by the operator.
     */
    public function resolveException(ShipmentException $exception, array $data, User $resolver): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data, $resolver) {
            $now = Carbon::now();

            $exception->update([
                'status' => ShipmentException::STATUS_RESOLVED,
                'resolved_at' => $now,
                'resolved_by' => $resolver->id,
                'resolution_notes' => $data['resolution_notes'] ?? 'Incident resolved and cleared by operations.',
            ]);

            // If operator specifies explicit ETA correction delta (e.g. -4.0 hours), apply it
            if (isset($data['eta_correction_hours'])) {
                $correctionDelta = (float) $data['eta_correction_hours'];
                if (abs($correctionDelta) > 0.001) {
                    $this->etaService->adjustEta($exception->shipment, $correctionDelta);
                }
            }

            // Log resolution tracking event
            $this->trackingService->recordIntermediateEvent($exception->shipment, 'EXCEPTION', [
                'event_time' => $now,
                'description' => "EXCEPTION RESOLVED: [{$exception->exception_type}] - " . ($data['resolution_notes'] ?? 'Resolved.'),
            ], $resolver);

            return $exception->fresh(['shipment', 'resolver']);
        });
    }
}
