<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationAndAuditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds realistic, role-specific in-app notifications and a robust regulatory audit trail.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@cargotrace.com')->first();
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $customer = User::where('email', 'customer@cargotrace.com')->first();

        // ================= SEED NOTIFICATIONS =================
        $notificationsData = [
            // Customer Notifications (Kargo & Dokumen Pengiriman Pelanggan)
            [
                'user_id' => $customer?->id,
                'title' => 'Peringatan Cuaca: Penyesuaian Jadwal Berlayar',
                'message' => 'Kapal pengangkut kontainer EGLV-3104928 mengalami penyesuaian kecepatan di Selat Malaka akibat gelombang laut tinggi (+18 jam).',
                'type' => 'WARNING',
                'data' => ['shipment_number' => 'CT-20261109-0002', 'severity' => 'MEDIUM'],
                'created_at' => Carbon::now()->subHours(5),
            ],
            [
                'user_id' => $customer?->id,
                'title' => 'Kargo Anda Sedang Dalam Pelayaran',
                'message' => 'Pengiriman CT-20261109-0001 (CNC Milling Machine Parts) telah dimuat di kapal Madrid Maersk dan berangkat menuju Rotterdam.',
                'type' => 'INFO',
                'data' => ['shipment_number' => 'CT-20261109-0001', 'container' => 'MSKU-7819201'],
                'created_at' => Carbon::now()->subHours(18),
            ],
            [
                'user_id' => $customer?->id,
                'title' => 'Kargo Tiba di Pelabuhan Tujuan',
                'message' => 'Pengiriman CT-20261109-0015 telah berhasil sandar di dermaga Pelabuhan Tanjung Priok. Silakan unduh dokumen kedatangan.',
                'type' => 'SUCCESS',
                'data' => ['shipment_number' => 'CT-20261109-0015'],
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'user_id' => $customer?->id,
                'title' => 'Validasi Dokumen Bea Cukai Selesai',
                'message' => 'Pemeriksaan Bill of Lading (B/L) dan deklarasi pabean untuk kontainer MSKU-8291043 telah disetujui tanpa kendala.',
                'type' => 'SUCCESS',
                'data' => ['shipment_number' => 'CT-20261109-0003'],
                'created_at' => Carbon::now()->subDays(2),
            ],

            // Operator Notifications (Operasional Armada, AIS, & Disposisi)
            [
                'user_id' => $operator?->id,
                'title' => 'Peringatan Antrean Sandar Pelabuhan (Port Congestion)',
                'message' => 'Terminal petikemas Rotterdam Maasvlakte melaporkan lonjakan waktu tunggu sandar kapal hingga 24 jam.',
                'type' => 'WARNING',
                'data' => ['port_code' => 'NLRTM', 'impact_hours' => 24],
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'user_id' => $operator?->id,
                'title' => 'Sinkronisasi Telemetri AIS Satelit Berhasil',
                'message' => 'Satelit AIS berhasil memperbarui koordinat posisi dan kecepatan jelajah untuk 5 kapal kontainer utama.',
                'type' => 'INFO',
                'data' => ['vessels_updated' => 5, 'source' => 'AIS_TERRESTRIAL_SATELLITE'],
                'created_at' => Carbon::now()->subHours(8),
            ],
            [
                'user_id' => $operator?->id,
                'title' => 'Insiden Bea Cukai Memerlukan Tindakan',
                'message' => 'Kontainer MSKU-8921021 pada pengiriman CT-20261109-0023 ditahan sementara untuk verifikasi dokumen Bahan Berbahaya (DG).',
                'type' => 'ERROR',
                'data' => ['shipment_number' => 'CT-20261109-0023', 'status' => 'CUSTOMS_HOLD'],
                'created_at' => Carbon::now()->subHours(14),
            ],
            [
                'user_id' => $operator?->id,
                'title' => 'Disposisi Rute Kapal Berhasil Diperbarui',
                'message' => 'Kapal MSC Gulsun telah menyelesaikan manuver penghindaran badai dan kembali ke kecepatan normal 19.4 knots.',
                'type' => 'SUCCESS',
                'data' => ['vessel' => 'MSC Gulsun', 'imo' => '9839438'],
                'created_at' => Carbon::now()->subDays(1),
            ],

            // Admin Notifications (Audit Sistem, Keamanan, & Valuasi Platform)
            [
                'user_id' => $admin?->id,
                'title' => 'Laporan Audit Integritas Sesi & RBAC',
                'message' => 'Seluruh upaya login antar 3 peran pengguna berhasil divalidasi dengan isolasi data ketat dan verifikasi kata sandi.',
                'type' => 'SUCCESS',
                'data' => ['audit_check' => 'PASSED', 'status' => 'SECURE'],
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'user_id' => $admin?->id,
                'title' => 'Ambang Batas Valuasi Kargo Global Tercapai',
                'message' => 'Total nilai kargo aktif yang terpantau pada jaringan pelabuhan global telah mencapai $ 1.620.000 USD.',
                'type' => 'INFO',
                'data' => ['total_valuation_usd' => 1620000, 'active_ports' => 50],
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'user_id' => $admin?->id,
                'title' => 'Gateway IoT AIS Collector Berjalan Stabil',
                'message' => 'Koneksi script Python ais_collector.py terhubung dengan latensi rata-rata 12ms tanpa paket data hilang.',
                'type' => 'INFO',
                'data' => ['status' => 'HEALTHY', 'gateway' => '127.0.0.1:8000'],
                'created_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($notificationsData as $item) {
            if ($item['user_id']) {
                Notification::updateOrCreate(
                    [
                        'user_id' => $item['user_id'],
                        'title' => $item['title'],
                    ],
                    [
                        'message' => $item['message'],
                        'type' => $item['type'],
                        'data' => $item['data'],
                        'read_at' => null,
                        'created_at' => $item['created_at'],
                        'updated_at' => $item['created_at'],
                    ]
                );
            }
        }

        // ================= SEED AUDIT LOGS =================
        $auditLogsData = [
            [
                'user_id' => $admin?->id,
                'action' => 'SYSTEM_CONFIG_UPDATED',
                'entity_type' => 'SystemSetting',
                'entity_id' => 1,
                'old_values' => ['strict_rbac' => false, 'session_logout_required' => false],
                'new_values' => ['strict_rbac' => true, 'session_logout_required' => true],
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_RESOLVED',
                'entity_type' => 'ShipmentException',
                'entity_id' => 5,
                'old_values' => ['status' => 'OPEN', 'severity' => 'LOW'],
                'new_values' => ['status' => 'RESOLVED', 'resolution_note' => 'Dokumen pelengkap kepabeanan telah diverifikasi oleh tim bea cukai pelabuhan.'],
                'ip_address' => '192.168.1.25',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'AIS_TELEMETRY_SYNC',
                'entity_type' => 'VesselPosition',
                'entity_id' => 12,
                'old_values' => ['speed_knots' => 14.2, 'lat' => -6.08, 'lng' => 106.85],
                'new_values' => ['speed_knots' => 18.5, 'lat' => 1.25, 'lng' => 103.85, 'heading' => 45],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Python/3.11 ais_collector.py',
                'created_at' => Carbon::now()->subHours(7),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'STATUS_TRANSITION',
                'entity_type' => 'Shipment',
                'entity_id' => 2,
                'old_values' => ['current_status' => 'PICKED_UP'],
                'new_values' => ['current_status' => 'IN_TRANSIT', 'vessel_id' => 2, 'container_number' => 'EGLV-3104928'],
                'ip_address' => '192.168.1.25',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'user_id' => $customer?->id,
                'action' => 'DOCUMENT_DOWNLOAD',
                'entity_type' => 'ShipmentDocument',
                'entity_id' => 15,
                'old_values' => null,
                'new_values' => ['document_type' => 'BILL_OF_LADING', 'shipment_number' => 'CT-20261109-0015'],
                'ip_address' => '182.253.14.92',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subHours(20),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_CREATED',
                'entity_type' => 'Shipment',
                'entity_id' => 1,
                'old_values' => null,
                'new_values' => [
                    'shipment_number' => 'CT-20261109-0001',
                    'container_number' => 'MSKU-7819201',
                    'origin' => 'Port of Tanjung Priok',
                    'destination' => 'Port of Rotterdam',
                    'declared_value' => 62000.0,
                ],
                'ip_address' => '192.168.1.25',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'user_id' => $admin?->id,
                'action' => 'USER_AUTHENTICATED',
                'entity_type' => 'User',
                'entity_id' => 1,
                'old_values' => null,
                'new_values' => ['event' => 'LOGIN_VERIFIED', 'method' => 'PASSWORD_HASH'],
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0.0.0',
                'created_at' => Carbon::now()->subDays(2),
            ],
        ];

        foreach ($auditLogsData as $log) {
            AuditLog::create($log);
        }
    }
}
