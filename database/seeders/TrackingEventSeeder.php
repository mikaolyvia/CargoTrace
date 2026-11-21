<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrackingEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds rich, chronological tracking events with real intermediate waypoints along maritime lanes.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $shipments = Shipment::with(['origin', 'destination', 'vessel'])->get();

        foreach ($shipments as $shipment) {
            // Delete old events to ensure clean, updated waypoints
            $shipment->trackingEvents()->delete();

            $departure = $shipment->expected_departure ?? Carbon::now()->subDays(6);
            $originLoc = $shipment->origin;
            $destLoc = $shipment->destination;
            $vessel = $shipment->vessel;

            // 1. ORDER_CREATED (At Origin Port)
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $originLoc->id,
                'created_by' => $operator->id,
                'event_type' => 'ORDER_CREATED',
                'event_time' => $departure->copy()->subDays(3),
                'description' => "Booking elektronik diterbitkan. Alokasi slot kontainer #{$shipment->container_number} disetujui pada carrier {$shipment->carrier?->name}.",
                'latitude' => $originLoc->latitude,
                'longitude' => $originLoc->longitude,
                'weather_condition' => 'Clear Sky',
                'weather_temp_c' => 30.0,
            ]);

            if (in_array($shipment->current_status, ['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'])) {
                // 2. BOOKING_CONFIRMED (At Origin Port)
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $originLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'BOOKING_CONFIRMED',
                    'event_time' => $departure->copy()->subDays(2),
                    'description' => "Ruang muat dikonfirmasi di atas kapal {$vessel?->name}. Dokumen Bill of Lading (B/L) resmi diterbitkan.",
                    'latitude' => $originLoc->latitude,
                    'longitude' => $originLoc->longitude,
                    'weather_condition' => 'Scattered Clouds',
                    'weather_temp_c' => 29.5,
                ]);
            }

            if (in_array($shipment->current_status, ['PICKED_UP', 'IN_TRANSIT', 'DELIVERED'])) {
                // 3. PICKED_UP (Gated-in and lashed at Origin terminal)
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $originLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'PICKED_UP',
                    'event_time' => $departure->copy()->subHours(8),
                    'description' => "Kontainer {$shipment->container_number} masuk gerbang dermaga {$originLoc->name}, pemeriksaan fisik dan timbang lolos verifikasi pabean.",
                    'latitude' => $originLoc->latitude,
                    'longitude' => $originLoc->longitude,
                    'weather_condition' => 'Partly Cloudy',
                    'weather_temp_c' => 28.5,
                ]);

                // 4. DEPARTED (Vessel departed berth)
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => null,
                    'created_by' => $operator->id,
                    'event_type' => 'DEPARTED',
                    'event_time' => $departure->copy(),
                    'description' => "Kapal {$vessel?->name} lepas tali dermaga {$originLoc->name} dan bertolak menuju koridor laut internasional.",
                    'latitude' => round($originLoc->latitude + ($destLoc->latitude > $originLoc->latitude ? 0.35 : -0.35), 4),
                    'longitude' => round($originLoc->longitude + 0.30, 4),
                    'weather_condition' => 'Calm Waters',
                    'weather_temp_c' => 28.0,
                ]);
            }

            if (in_array($shipment->current_status, ['IN_TRANSIT', 'DELIVERED'])) {
                // Determine realistic intermediate maritime waypoints depending on route
                $isEuropeRoute = in_array($destLoc->code, ['NLRTM', 'DEHAM', 'AMS']);
                $isEastAsiaRoute = in_array($destLoc->code, ['CNSHA', 'JPTYO']);

                if ($isEuropeRoute) {
                    // Waypoint A: Transit at Port of Singapore
                    $sg = Location::where('code', 'SGSIN')->first();
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => $sg?->id,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(1)->addHours(6),
                        'description' => "Transit dan bunker bunkering di Pelabuhan Singapura (PSA Terminal). Pemeriksaan seal kontainer utuh.",
                        'latitude' => 1.2644,
                        'longitude' => 103.8222,
                        'weather_condition' => 'Light Rain',
                        'weather_temp_c' => 27.5,
                    ]);

                    // Waypoint B: Malacca Strait TSS
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(2),
                        'description' => "Navigasi Traffic Separation Scheme (TSS) Selat Malaka. Kecepatan jelajah 17.8 knot pada cuaca cerah.",
                        'latitude' => 3.2000,
                        'longitude' => 100.5000,
                        'weather_condition' => 'Gentle Breeze',
                        'weather_temp_c' => 28.0,
                    ]);

                    // Waypoint C: Indian Ocean south of Sri Lanka
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(4),
                        'description' => "Pelayaran Samudra Hindia perairan selatan Sri Lanka. Posisi telemetri satelit terpantau normal.",
                        'latitude' => 5.8500,
                        'longitude' => 80.5500,
                        'weather_condition' => 'Moderate Swell',
                        'weather_temp_c' => 26.5,
                    ]);

                    // Waypoint D: Bab-el-Mandeb Chokepoint
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(8),
                        'description' => "Memasuki Selat Bab-el-Mandeb menuju Laut Merah dengan protokol konvoi navigasi maritim.",
                        'latitude' => 12.5000,
                        'longitude' => 44.0000,
                        'weather_condition' => 'Sunny & Clear',
                        'weather_temp_c' => 31.0,
                    ]);

                    // Waypoint E: Suez Canal Transit
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(12),
                        'description' => "Konvoi Terusan Suez utara menuju Laut Mediterania. Pandu laut bertugas.",
                        'latitude' => 30.5000,
                        'longitude' => 32.5000,
                        'weather_condition' => 'Clear Sky',
                        'weather_temp_c' => 24.0,
                    ]);

                    // Waypoint F: Strait of Gibraltar
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(16),
                        'description' => "Melewati Selat Gibraltar menuju Samudra Atlantik Utara menuju pelabuhan Eropa Barat.",
                        'latitude' => 36.0000,
                        'longitude' => -5.3500,
                        'weather_condition' => 'Fresh Breeze',
                        'weather_temp_c' => 19.5,
                    ]);
                } elseif ($isEastAsiaRoute) {
                    // Waypoint A: Karimata Strait
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(1),
                        'description' => "Navigasi Selat Karimata menuju perairan Laut Natuna / Laut China Selatan.",
                        'latitude' => -1.8000,
                        'longitude' => 107.5000,
                        'weather_condition' => 'Scattered Clouds',
                        'weather_temp_c' => 29.0,
                    ]);

                    // Waypoint B: South China Sea Corridor
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(3),
                        'description' => "Pelayaran koridor utama Laut China Selatan pada haluan 35° menuju Selat Taiwan.",
                        'latitude' => 11.5000,
                        'longitude' => 112.5000,
                        'weather_condition' => 'Moderate Breeze',
                        'weather_temp_c' => 28.5,
                    ]);

                    // Waypoint C: Taiwan Strait / East China Sea
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addDays(5),
                        'description' => "Memasuki perairan Laut China Timur menuju muara Sungai Yangtze / Teluk Tokyo.",
                        'latitude' => 25.0000,
                        'longitude' => 121.5000,
                        'weather_condition' => 'Overcast',
                        'weather_temp_c' => 22.0,
                    ]);
                } else {
                    // Regional Feeder Route (e.g. Jakarta -> Singapore)
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => null,
                        'created_by' => $operator->id,
                        'event_type' => 'IN_TRANSIT',
                        'event_time' => $departure->copy()->addHours(14),
                        'description' => "Pelayaran jalur feeder Selat Gaspar / Selat Bangka menuju Singapura.",
                        'latitude' => -2.2500,
                        'longitude' => 105.8500,
                        'weather_condition' => 'Clear Sky',
                        'weather_temp_c' => 28.5,
                    ]);
                }
            }

            if ($shipment->current_status === 'DELIVERED') {
                $actual = $shipment->actual_arrival ?? Carbon::now()->subDays(1);
                // Arrival Event
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $destLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'ARRIVED',
                    'event_time' => $actual->copy()->subHours(3),
                    'description' => "Kapal merapat di dermaga {$destLoc->name}. Pengawasan pandu pelabuhan selesai.",
                    'latitude' => $destLoc->latitude,
                    'longitude' => $destLoc->longitude,
                    'weather_condition' => 'Calm Sea',
                    'weather_temp_c' => 18.0,
                ]);

                // Delivered Event
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $destLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'DELIVERED',
                    'event_time' => $actual,
                    'description' => "Kontainer selesai dibongkar dari palka kapal, lolos pemeriksaan pabean impor, dan diserahkan ke terminal penerima.",
                    'latitude' => $destLoc->latitude,
                    'longitude' => $destLoc->longitude,
                    'weather_condition' => 'Partly Cloudy',
                    'weather_temp_c' => 18.5,
                ]);
            }
        }
    }
}
