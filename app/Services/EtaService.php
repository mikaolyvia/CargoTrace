<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\SystemSetting;
use Carbon\Carbon;

class EtaService
{
    /**
     * Determine delay hours and severity for a shipment based on deterministic business rules.
     *
     * Rules:
     * - If DELIVERED: Baseline is actual_arrival (historical actual delay).
     * - If NOT DELIVERED: Baseline is estimated_arrival (projected delay).
     * - Thresholds loaded from SystemSetting (defaults: LOW <= 6h, MEDIUM <= 24h, HIGH > 24h).
     *
     * @param Shipment $shipment
     * @return array{is_delayed: bool, delay_hours: float, delay_severity: string}
     */
    public function calculateDelay(Shipment $shipment): array
    {
        $expected = $shipment->expected_arrival;

        // 1. Determine baseline
        $baseline = ($shipment->current_status === 'DELIVERED' || $shipment->actual_arrival !== null)
            ? ($shipment->actual_arrival ?? $shipment->estimated_arrival)
            : $shipment->estimated_arrival;

        if (! $baseline || ! $expected) {
            return [
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
            ];
        }

        // 2. Compute delay in hours
        if ($baseline->gt($expected)) {
            $diffMinutes = $expected->diffInMinutes($baseline);
            $delayHours = round($diffMinutes / 60, 2);
        } else {
            $delayHours = 0.00;
        }

        // 3. Load dynamic thresholds
        $lowThreshold = SystemSetting::getNumeric('delay_threshold_low', 6.0);
        $mediumThreshold = SystemSetting::getNumeric('delay_threshold_medium', 24.0);

        if ($delayHours <= 0.00) {
            return [
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
            ];
        }

        if ($delayHours <= $lowThreshold) {
            $severity = 'LOW';
        } elseif ($delayHours <= $mediumThreshold) {
            $severity = 'MEDIUM';
        } else {
            $severity = 'HIGH';
        }

        return [
            'is_delayed' => true,
            'delay_hours' => $delayHours,
            'delay_severity' => $severity,
        ];
    }

    /**
     * Recalculate and persist delay metrics for a shipment.
     */
    public function recalculateDelay(Shipment $shipment): Shipment
    {
        $metrics = $this->calculateDelay($shipment);

        $shipment->update($metrics);

        return $shipment;
    }

    /**
     * Adjust estimated arrival by delta hours and recalculate delay metrics.
     * Positive delta shifts ETA into future; negative delta advances it earlier.
     */
    public function adjustEta(Shipment $shipment, float $deltaHours): Shipment
    {
        if (abs($deltaHours) > 0.001) {
            $currentEta = $shipment->estimated_arrival ?? $shipment->expected_arrival;
            $newEta = $currentEta->copy()->addMinutes((int) round($deltaHours * 60));

            $shipment->estimated_arrival = $newEta;
        }

        return $this->recalculateDelay($shipment);
    }
}
