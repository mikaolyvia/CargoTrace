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

        // 1. Shipment 1: DOCUMENTATION_ISSUE (OPEN, MEDIUM, 8 hours impact)
        $shipment1 = Shipment::where('shipment_number', 'CT-20261109-0001')->first();
        if ($shipment1 && $shipment1->exceptions()->count() === 0) {
            $exceptionService->reportException($shipment1, [
                'exception_type' => 'DOCUMENTATION_ISSUE',
                'severity' => 'MEDIUM',
                'impact_hours' => 8.0,
                'description' => 'Commercial invoice item description discrepancy detected during export validation.',
                'detected_at' => Carbon::now()->subHours(4),
            ], $operator);
        }

        // 2. Shipment 2: WEATHER_DELAY (INVESTIGATING, HIGH, 28 hours impact)
        $shipment2 = Shipment::where('shipment_number', 'CT-20261109-0002')->first();
        if ($shipment2 && $shipment2->exceptions()->count() === 0) {
            $ex2 = $exceptionService->reportException($shipment2, [
                'exception_type' => 'WEATHER_DELAY',
                'severity' => 'HIGH',
                'impact_hours' => 28.0,
                'description' => 'Severe maritime storm advisory along Malacca Strait slowing vessel transit speed.',
                'detected_at' => Carbon::now()->subHours(12),
            ], $operator);

            $exceptionService->investigateException($ex2, $operator, 'Operations team actively monitoring storm radar and rerouting options.');
        }

        // 3. Shipment 3: CUSTOMS_HOLD (RESOLVED, LOW, 4 hours impact)
        $shipment3 = Shipment::where('shipment_number', 'CT-20261109-0003')->first();
        if ($shipment3 && $shipment3->exceptions()->count() === 0) {
            $ex3 = $exceptionService->reportException($shipment3, [
                'exception_type' => 'CUSTOMS_HOLD',
                'severity' => 'LOW',
                'impact_hours' => 4.0,
                'description' => 'Air cargo spot check for sensitive electronic dual-use components.',
                'detected_at' => Carbon::now()->subHours(6),
            ], $operator);

            $exceptionService->resolveException($ex3, [
                'resolution_notes' => 'Export declaration verified and cleared under HS code 3818.00.00 green lane.',
            ], $operator);
        }
    }
}
