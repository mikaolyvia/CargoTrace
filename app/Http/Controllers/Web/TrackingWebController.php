<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\Vessel;
use App\Models\VesselPosition;
use App\Services\CurrencyService;
use App\Services\TrackingService;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackingWebController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected WeatherService $weatherService,
        protected TrackingService $trackingService
    ) {}

    /**
     * Render the Live Maritime Tracking & AIS Map view.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isCustomer = $user && $user->isCustomer();

        // Query parameters
        $code = $request->query('code');
        $search = trim($request->query('q', $request->query('search', $request->query('region', ''))));
        $vesselId = $request->query('vessel_id');
        $country = $request->query('country');
        $portId = $request->query('port_id');
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Base Query scoped by role
        $query = Shipment::with([
            'carrier:id,name,code',
            'customer:id,name,email',
            'origin:id,name,city,country,currency_code,currency_symbol,latitude,longitude',
            'destination:id,name,city,country,currency_code,currency_symbol,latitude,longitude',
            'currentLocation:id,name,city,country,latitude,longitude',
            'vessel:id,name,imo_number,mmsi,status,flag_country,last_latitude,last_longitude,last_speed_knots,last_heading_degrees,last_position_at',
            'trackingEvents' => fn ($q) => $q->orderBy('event_time', 'asc'),
            'items',
            'exceptions',
        ])->orderByDesc('created_at');

        // Role-based customer isolation
        if ($isCustomer) {
            $query->where('customer_id', $user->id);
        }

        // Filter: Search Keyword
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('container_number', 'like', "%{$search}%")
                  ->orWhere('cargo_type', 'like', "%{$search}%")
                  ->orWhereHas('destination', function ($locQ) use ($search) {
                      $locQ->where('country', 'like', "%{$search}%")
                           ->orWhere('city', 'like', "%{$search}%")
                           ->orWhere('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('origin', function ($locQ) use ($search) {
                      $locQ->where('country', 'like', "%{$search}%")
                           ->orWhere('city', 'like', "%{$search}%")
                           ->orWhere('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vessel', function ($vQ) use ($search) {
                      $vQ->where('name', 'like', "%{$search}%")
                         ->orWhere('imo_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items', function ($itemQ) use ($search) {
                      $itemQ->where('item_name', 'like', "%{$search}%")
                            ->orWhere('hs_code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Vessel
        if ($vesselId) {
            $query->where('vessel_id', $vesselId);
        }

        // Filter: Country (Origin or Destination)
        if ($country) {
            $query->where(function ($q) use ($country) {
                $q->whereHas('destination', fn ($locQ) => $locQ->where('country', $country))
                  ->orWhereHas('origin', fn ($locQ) => $locQ->where('country', $country));
            });
        }

        // Filter: Port
        if ($portId) {
            $query->where(function ($q) use ($portId) {
                $q->where('origin_id', $portId)
                  ->orWhere('destination_id', $portId);
            });
        }

        // Filter: Status (including DELAYED pseudo-status)
        if ($status) {
            if ($status === 'DELAYED') {
                $query->where('is_delayed', true);
            } else {
                $query->where('current_status', $status);
            }
        }

        // Filter: Date Range
        if ($startDate) {
            $query->whereDate('expected_departure', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('expected_arrival', '<=', $endDate);
        }

        $shipments = $query->get();

        // Dynamic Summary Statistics based on active query
        $summary = [
            'total_monitored' => $shipments->count(),
            'total_vessels' => $shipments->pluck('vessel_id')->filter()->unique()->count(),
            'in_transit_count' => $shipments->where('current_status', 'IN_TRANSIT')->count(),
            'delayed_count' => $shipments->where('is_delayed', true)->count(),
            'arrived_count' => $shipments->where('current_status', 'DELIVERED')->count(),
        ];

        // Selected shipment resolution
        $selectedShipment = null;
        if ($code) {
            $selectedShipment = $shipments->firstWhere('tracking_number', $code);
        }

        if (! $selectedShipment && $shipments->isNotEmpty()) {
            $selectedShipment = $shipments->first();
        }

        // Calculate Multi-Currency Valuation & Live Weather for selected shipment
        $valuation = null;
        $vesselWeather = null;
        $destinationWeather = null;
        $destinationPortActivity = null;
        $etaDetails = null;

        if ($selectedShipment) {
            $baseAmount = (float) ($selectedShipment->total_declared_value ?? 0.0);
            $baseCurrency = $selectedShipment->currency ?? 'USD';

            $valuation = $this->currencyService->calculateShipmentValuation(
                $baseAmount,
                $baseCurrency,
                $selectedShipment->origin?->country,
                $selectedShipment->destination?->country
            );

            // Compute item conversions for the destination country currency
            $destCurrencyCode = $valuation['destination']['code'];
            $selectedShipment->items->each(function ($item) use ($baseCurrency, $destCurrencyCode) {
                $item->unit_price_dest = $this->currencyService->convert((float) $item->unit_price, $baseCurrency, $destCurrencyCode);
                $item->total_price_dest = $this->currencyService->convert((float) $item->total_price, $baseCurrency, $destCurrencyCode);
                $item->unit_price_dest_formatted = $this->currencyService->format($item->unit_price_dest, $destCurrencyCode);
                $item->total_price_dest_formatted = $this->currencyService->format($item->total_price_dest, $destCurrencyCode);
            });

            // Vessel Live Marine Weather
            if ($selectedShipment->vessel && $selectedShipment->vessel->last_latitude && $selectedShipment->vessel->last_longitude) {
                $vesselWeather = $this->weatherService->getCurrentWeather(
                    (float) $selectedShipment->vessel->last_latitude,
                    (float) $selectedShipment->vessel->last_longitude
                );
            }

            // Destination Port Weather & Activity
            if ($selectedShipment->destination && $selectedShipment->destination->latitude && $selectedShipment->destination->longitude) {
                $destinationWeather = $this->weatherService->getCurrentWeather(
                    (float) $selectedShipment->destination->latitude,
                    (float) $selectedShipment->destination->longitude
                );

                // Port Activity / Inbound Congestion metric
                $inboundCount = Shipment::where('destination_id', $selectedShipment->destination_id)
                    ->whereIn('current_status', ['IN_TRANSIT', 'BOOKED'])
                    ->count();

                $destinationPortActivity = [
                    'inbound_vessels' => $inboundCount,
                    'congestion_level' => $inboundCount > 5 ? 'High Congestion' : ($inboundCount > 2 ? 'Moderate' : 'Normal Traffic'),
                    'status' => 'Operasional Normal (Open 24/7)',
                ];
            }

            // ETA Comparison & Deterministic Delay Rules (0-6h LOW, 6-24h MEDIUM, >24h HIGH)
            $expectedArrival = $selectedShipment->expected_arrival;
            $estimatedArrival = $selectedShipment->estimated_arrival;
            $delayHours = 0.0;
            $severity = 'NONE';

            if ($expectedArrival && $estimatedArrival && $estimatedArrival->gt($expectedArrival)) {
                $delayHours = round($estimatedArrival->diffInMinutes($expectedArrival) / 60, 1);
                if ($delayHours <= 6) {
                    $severity = 'LOW';
                } elseif ($delayHours <= 24) {
                    $severity = 'MEDIUM';
                } else {
                    $severity = 'HIGH';
                }
            }

            $etaDetails = [
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'delay_hours' => $delayHours,
                'severity' => $severity,
                'is_delayed' => $delayHours > 0 || (bool) $selectedShipment->is_delayed,
                'difference_label' => $delayHours > 0 ? "+{$delayHours} Jam Keterlambatan ({$severity})" : "Sesuai Jadwal (On Schedule)",
            ];
        }

        // Master Data for Select Filters
        $vesselsList = Vessel::orderBy('name')->get(['id', 'name', 'imo_number', 'status']);
        $portsList = Location::where('type', 'PORT')->orderBy('name')->get(['id', 'code', 'name', 'city', 'country']);
        $countriesList = Location::distinct()->pluck('country')->filter()->values();
        $statusesList = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'DELAYED'];

        return view('tracking.map', [
            'shipments' => $shipments,
            'selectedShipment' => $selectedShipment,
            'summary' => $summary,
            'valuation' => $valuation,
            'vesselWeather' => $vesselWeather,
            'destinationWeather' => $destinationWeather,
            'destinationPortActivity' => $destinationPortActivity,
            'etaDetails' => $etaDetails,
            'rates' => $this->currencyService->getRates(),
            'vesselsList' => $vesselsList,
            'portsList' => $portsList,
            'countriesList' => $countriesList,
            'statusesList' => $statusesList,
            'filters' => [
                'search' => $search,
                'vessel_id' => $vesselId,
                'country' => $country,
                'port_id' => $portId,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'currencyService' => $this->currencyService,
            'user' => $user,
        ]);
    }

    /**
     * Export tracking voyage log and shipment details to CSV format.
     */
    public function export(Request $request): StreamedResponse
    {
        $code = $request->query('code');
        $shipment = Shipment::with([
            'carrier',
            'origin',
            'destination',
            'vessel',
            'trackingEvents' => fn ($q) => $q->orderBy('event_time', 'asc'),
            'items',
            'exceptions',
        ])->where('shipment_number', $code)->firstOrFail();

        $filename = "CargoTrace-Voyage-{$shipment->shipment_number}-" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($shipment) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel compatibility

            // 1. Header Information
            fputcsv($handle, ['CARGOTRACE MARITIME SHIPMENT REPORT']);
            fputcsv($handle, ['Tracking Number', $shipment->shipment_number]);
            fputcsv($handle, ['Container Number', $shipment->container_number ?? '-']);
            fputcsv($handle, ['Ocean Carrier', $shipment->carrier->name ?? '-']);
            fputcsv($handle, ['Vessel', ($shipment->vessel->name ?? '-') . " (IMO: " . ($shipment->vessel->imo_number ?? '-') . ")"]);
            fputcsv($handle, ['Origin Port', ($shipment->origin->name ?? '-') . " (" . ($shipment->origin->country ?? '-') . ")"]);
            fputcsv($handle, ['Destination Port', ($shipment->destination->name ?? '-') . " (" . ($shipment->destination->country ?? '-') . ")"]);
            fputcsv($handle, ['Cargo Type', $shipment->cargo_type]);
            fputcsv($handle, ['Total Declared Value', $shipment->currency . ' ' . number_format($shipment->total_declared_value, 2)]);
            fputcsv($handle, ['Status', $shipment->current_status]);
            fputcsv($handle, ['Expected Arrival', $shipment->expected_arrival ? $shipment->expected_arrival->toIso8601String() : '-']);
            fputcsv($handle, ['Estimated Arrival', $shipment->estimated_arrival ? $shipment->estimated_arrival->toIso8601String() : '-']);
            fputcsv($handle, []);

            // 2. Item Manifest
            fputcsv($handle, ['CARGO MANIFEST ITEMS']);
            fputcsv($handle, ['Item Name', 'HS Code', 'Quantity', 'Weight (kg)', 'Unit Price (USD)', 'Total Price (USD)']);
            foreach ($shipment->items as $item) {
                fputcsv($handle, [
                    $item->item_name,
                    $item->hs_code ?? '-',
                    $item->quantity,
                    $item->weight_kg,
                    number_format($item->unit_price, 2),
                    number_format($item->total_price, 2),
                ]);
            }
            fputcsv($handle, []);

            // 3. Chronological Tracking Timeline
            fputcsv($handle, ['CHRONOLOGICAL VOYAGE TIMELINE']);
            fputcsv($handle, ['Event Type', 'Date & Time (UTC)', 'Location', 'Coordinates', 'Weather Condition', 'Temperature', 'Notes']);
            foreach ($shipment->trackingEvents as $ev) {
                fputcsv($handle, [
                    $ev->event_type,
                    $ev->event_time ? $ev->event_time->toIso8601String() : '-',
                    $ev->location_name ?? '-',
                    ($ev->latitude && $ev->longitude) ? "{$ev->latitude}, {$ev->longitude}" : '-',
                    $ev->weather_condition ?? '-',
                    $ev->weather_temp_c !== null ? "{$ev->weather_temp_c}°C" : '-',
                    $ev->notes ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Simulate a live AIS telemetry ping for demo purposes (advances vessel position and records intermediate event).
     */
    public function simulateAis(Request $request)
    {
        $shipmentId = $request->input('shipment_id');
        $shipment = Shipment::with(['vessel', 'origin', 'destination'])->findOrFail($shipmentId);

        if (! $shipment->vessel) {
            return back()->with('error', 'Shipment ini belum memiliki kapal yang ditugaskan.');
        }

        $vessel = $shipment->vessel;

        // Advance position slightly toward destination or along maritime corridor
        $currentLat = (float) ($vessel->last_latitude ?? $shipment->origin->latitude);
        $currentLng = (float) ($vessel->last_longitude ?? $shipment->origin->longitude);
        $destLat = (float) $shipment->destination->latitude;
        $destLng = (float) $shipment->destination->longitude;

        // Step 15% towards destination
        $newLat = round($currentLat + ($destLat - $currentLat) * 0.15, 6);
        $newLng = round($currentLng + ($destLng - $currentLng) * 0.15, 6);
        $speed = rand(170, 215) / 10; // 17.0 to 21.5 knots
        $heading = rand(270, 310);

        // Update vessel position
        $vessel->update([
            'last_latitude' => $newLat,
            'last_longitude' => $newLng,
            'last_speed_knots' => $speed,
            'last_heading_degrees' => $heading,
            'last_position_at' => now(),
            'status' => 'UNDERWAY',
        ]);

        // Record historical telemetry
        VesselPosition::create([
            'vessel_id' => $vessel->id,
            'latitude' => $newLat,
            'longitude' => $newLng,
            'speed_knots' => $speed,
            'heading_degrees' => $heading,
            'nav_status' => 'UNDERWAY_USING_ENGINE',
            'source' => 'AIS_SIMULATOR',
            'recorded_at' => now(),
        ]);

        // Record tracking event through TrackingService with automatic weather and actor attribution
        $actor = $request->user() ?? \App\Models\User::where('role', 'OPERATOR')->first() ?? $shipment->creator;

        $this->trackingService->recordIntermediateEvent(
            $shipment,
            'IN_TRANSIT',
            [
                'latitude' => $newLat,
                'longitude' => $newLng,
                'event_time' => now(),
                'description' => "Pembaruan telemetri otomatis satelit AIS. Kecepatan: {$speed} knots, Haluan: {$heading}°.",
            ],
            $actor
        );

        return back()->with('success', "Telemetri satelit AIS berhasil diperbarui untuk kapal {$vessel->name}. Koordinat baru: [{$newLat}, {$newLng}] pada kecepatan {$speed} knots.");
    }
}
