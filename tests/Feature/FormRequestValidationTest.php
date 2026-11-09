<?php

namespace Tests\Feature;

use App\Http\Requests\Api\ReportExceptionRequest;
use App\Http\Requests\Api\ResolveExceptionRequest;
use App\Http\Requests\Api\StoreShipmentRequest;
use App\Http\Requests\Api\StoreTrackingEventRequest;
use App\Http\Requests\Api\TransitionStatusRequest;
use App\Http\Requests\Api\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;
    public function test_store_shipment_request_rejects_non_customer_user_role(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::first() ?? Carrier::factory()->create();
        $origin = Location::first() ?? Location::factory()->create();
        $dest = Location::skip(1)->first() ?? Location::factory()->create();

        $data = [
            'customer_id' => $operator->id, // Not a CUSTOMER role!
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'GENERAL',
            'expected_departure' => now()->toDateTimeString(),
            'expected_arrival' => now()->addDays(3)->toDateTimeString(),
            'items' => [
                [
                    'item_description' => 'Test Item',
                    'quantity' => 10,
                    'weight_kg' => 50,
                ],
            ],
        ];

        $request = new StoreShipmentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());

        $operator->delete();
    }

    public function test_store_shipment_request_prohibits_client_injection_of_status_and_creator(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $carrier = Carrier::first() ?? Carrier::factory()->create();
        $origin = Location::first() ?? Location::factory()->create();
        $dest = Location::skip(1)->first() ?? Location::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'GENERAL',
            'expected_departure' => now()->toDateTimeString(),
            'expected_arrival' => now()->addDays(3)->toDateTimeString(),
            'created_by' => 999, // Injected!
            'current_status' => 'DELIVERED', // Injected!
            'items' => [
                [
                    'item_description' => 'Test Item',
                    'quantity' => 10,
                    'weight_kg' => 50,
                ],
            ],
        ];

        $request = new StoreShipmentRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('created_by', $validator->errors()->toArray());
        $this->assertArrayHasKey('current_status', $validator->errors()->toArray());

        $customer->delete();
    }

    public function test_update_shipment_request_prohibits_direct_status_change(): void
    {
        $request = new UpdateShipmentRequest();

        // Attempting to alter status via regular update payload
        $data = [
            'current_status' => 'DELIVERED',
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('current_status', $validator->errors()->toArray());
    }

    public function test_transition_status_request_validates_valid_and_invalid_macro_statuses(): void
    {
        $request = new TransitionStatusRequest();

        // Valid transition target
        $validData = [
            'status' => 'IN_TRANSIT',
        ];
        $validValidator = Validator::make($validData, $request->rules());
        $this->assertFalse($validValidator->fails());

        // Invalid target status (e.g. invalid string or CREATED)
        $invalidData = [
            'status' => 'INVALID_STATUS',
        ];
        $invalidValidator = Validator::make($invalidData, $request->rules());
        $this->assertTrue($invalidValidator->fails());
        $this->assertArrayHasKey('status', $invalidValidator->errors()->toArray());
    }

    public function test_store_tracking_event_request_validates_event_type_and_prohibits_created_by(): void
    {
        $request = new StoreTrackingEventRequest();

        $data = [
            'event_type' => 'INVALID_EVENT',
            'description' => 'Checkpoint test',
            'created_by' => 1,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('event_type', $validator->errors()->toArray());
        $this->assertArrayHasKey('created_by', $validator->errors()->toArray());
    }

    public function test_report_and_resolve_exception_requests_prohibit_user_overrides(): void
    {
        $reportRequest = new ReportExceptionRequest();
        $reportData = [
            'exception_type' => 'WEATHER_DELAY',
            'severity' => 'HIGH',
            'description' => 'Heavy typhoon',
            'reported_by' => 99, // Should be prohibited
        ];

        $reportValidator = Validator::make($reportData, $reportRequest->rules());
        $this->assertTrue($reportValidator->fails());
        $this->assertArrayHasKey('reported_by', $reportValidator->errors()->toArray());

        $resolveRequest = new ResolveExceptionRequest();
        $resolveData = [
            'resolution_notes' => 'Customs cleared after document submission',
            'resolved_by' => 99, // Should be prohibited
        ];

        $resolveValidator = Validator::make($resolveData, $resolveRequest->rules());
        $this->assertTrue($resolveValidator->fails());
        $this->assertArrayHasKey('resolved_by', $resolveValidator->errors()->toArray());
    }
}
