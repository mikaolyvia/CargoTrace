<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\ExceptionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShipmentExceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds realistic exceptions on demo shipments.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $exceptionService = app(ExceptionService::class);

        // 1. Shipment CT-20261109-0002: WEATHER_DELAY (INVESTIGATING, MEDIUM, 18 hours impact)
        $shipment2 = Shipment::where('shipment_number', 'CT-20261109-0002')->first();
        if ($shipment2 && $shipment2->exceptions()->count() === 0) {
            $ex2 = $exceptionService->reportException($shipment2, [
                'exception_type' => 'WEATHER_DELAY',
                'severity' => 'MEDIUM',
                'impact_hours' => 18.0,
                'description' => 'Severe maritime storm advisory along Malacca Strait slowing vessel transit speed.',
                'detected_at' => Carbon::now()->subHours(12),
            ], $operator);

            $exceptionService->investigateException($ex2, $operator, 'Operations team actively monitoring storm radar and rerouting options.');
        }

        // 2. Shipment CT-20261109-0023: CUSTOMS_HOLD (OPEN, HIGH, 28 hours impact)
        $shipment23 = Shipment::where('shipment_number', 'CT-20261109-0023')->first();
        if ($shipment23 && $shipment23->exceptions()->count() === 0) {
            $exceptionService->reportException($shipment23, [
                'exception_type' => 'CUSTOMS_HOLD',
                'severity' => 'HIGH',
                'impact_hours' => 28.0,
                'description' => 'Hazardous materials documentation flag during pre-clearance validation for battery electrolytes.',
                'detected_at' => Carbon::now()->subHours(6),
            ], $operator);
        }

        // 3. Shipment CT-20261109-0024: PORT_CONGESTION (OPEN, MEDIUM, 10 hours impact)
        $shipment24 = Shipment::where('shipment_number', 'CT-20261109-0024')->first();
        if ($shipment24 && $shipment24->exceptions()->count() === 0) {
            $exceptionService->reportException($shipment24, [
                'exception_type' => 'PORT_CONGESTION',
                'severity' => 'MEDIUM',
                'impact_hours' => 10.0,
                'description' => 'Container terminal berth queue bottleneck causing delayed vessel discharge slot.',
                'detected_at' => Carbon::now()->subHours(4),
            ], $operator);
        }

        // 4. Shipment CT-20261109-0005: PORT_CONGESTION (INVESTIGATING, HIGH, 32 hours impact)
        $shipment5 = Shipment::where('shipment_number', 'CT-20261109-0005')->first();
        if ($shipment5 && $shipment5->exceptions()->count() === 0) {
            $ex5 = $exceptionService->reportException($shipment5, [
                'exception_type' => 'PORT_CONGESTION',
                'severity' => 'HIGH',
                'impact_hours' => 32.0,
                'description' => 'Suez Canal anchorage queue bottleneck causing convoy entry reschedule.',
                'detected_at' => Carbon::now()->subHours(16),
            ], $operator);

            $exceptionService->investigateException($ex5, $operator, 'Vessel captain instructed to maintain eco-speed while waiting for convoy slot.');
        }

        // 5. Shipment CT-20261109-0009: DOCUMENTATION_ISSUE (RESOLVED, LOW, 4 hours impact)
        $shipment9 = Shipment::where('shipment_number', 'CT-20261109-0009')->first();
        if ($shipment9 && $shipment9->exceptions()->count() === 0) {
            $ex9 = $exceptionService->reportException($shipment9, [
                'exception_type' => 'DOCUMENTATION_ISSUE',
                'severity' => 'LOW',
                'impact_hours' => 4.0,
                'description' => 'Phytosanitary certificate digital endorsement re-submitted and cleared.',
                'detected_at' => Carbon::now()->subDays(4),
            ], $operator);

            $exceptionService->resolveException($ex9, [
                'resolution_notes' => 'Import health inspection authority granted green seal clearance.',
            ], $operator);
        }
    }
}
