# CargoTrace — Global Maritime Shipment Visibility & Automated Tracking Platform

CargoTrace adalah platform visibilitas pengiriman kargo maritim global berbasis Laravel 12, MariaDB, Leaflet.js, dan integrasi telemetri otomatis. Sistem ini dirancang untuk memantau perjalanan kapal kontainer internasional dari pelabuhan asal (origin), pelabuhan transit, hingga pelabuhan tujuan (destination) dengan akurasi koordinat AIS, deteksi keterlambatan otomatis (ETA/delay), serta peringatan cuaca maritim.

---

## 🏗️ Arsitektur & Teknologi

- **Backend:** Laravel 12 (PHP 8.2+) dengan arsitektur Service-Repository, Form Request Validation, API Resources, dan Strict State Machine.
- **Database:** MariaDB / MySQL dengan indexed foreign keys, compound indexes, dan soft deletes.
- **Frontend & Peta:** Blade Templates, Tailwind CSS, Leaflet.js (Peta Maritim Interaktif OpenStreetMap), Chart.js (Grafik Distribusi).
- **Integrasi Eksternal:**
  - **Open-Meteo API:** Pemantauan cuaca laut pelabuhan & waypoint maritim secara otomatis tanpa token berbayar.
  - **Frankfurter API:** Valuasi nilai pabean kargo multi-mata uang (USD, IDR, EUR, SGD, JPY, CNY) dengan fallback otomatis.
  - **Python AIS Collector (`python/ais_collector.py`):** Modul ingest data telemetri posisi kapal via REST API `/api/integrations/vessel-positions`.
- **Testing:** PHPUnit / Pest dengan 121 automated unit & feature tests.

---

## 👥 3 Role Pengguna & Hak Akses (RBAC)

1. **Administrator (`admin@cargotrace.com` / `password`)**
   - Akses penuh ke seluruh data armada kapal, pelabuhan, kargo, jejak audit (Audit Logs), dan konfigurasi sistem.
   - Otoritas untuk melakukan soft-delete atau manajemen entitas sistem.
2. **Logistics Operator (`operator@cargotrace.com` / `password`)**
   - Mendaftarkan pengiriman kargo baru (manifest booking).
   - Melakukan transisi status linear (`CREATED` &rarr; `BOOKED` &rarr; `PICKED_UP` &rarr; `IN_TRANSIT` &rarr; `DELIVERED`).
   - Melaporkan dan menyelesaikan kendala operasional (Shipment Exceptions).
   - Mengoperasikan simulasi pelayaran AIS kapal.
3. **Customer (`customer@cargotrace.com` / `password`)**
   - Akses visual hanya terhadap pengiriman miliknya (pencegahan IDOR ketat via scoped query).
   - Melacak lokasi kontainer di peta maritim interaktif, melihat status perjalanan (timeline), dan mengunduh laporan tracking CSV.

---

## 🚢 Fitur Utama Platform

### 1. Interactive Maritime Tracking Map (`/tracking-map`)
- Visualisasi pelayaran kapal internasional dengan Leaflet.js:
  - Pelabuhan asal (Origin Port - Hijau)
  - Pelabuhan transit (Transit Port - Oranye)
  - Pelabuhan tujuan (Destination Port - Merah)
  - Marker kapal kargo berlayar (Kapal - Biru) dengan haluan derajat dan kecepatan
  - Jalur pelayaran laut (Geodesic Great Circle Polylines)
- Navigasi peta: Zoom in/out, panning halus, tombol pintas `🧭 Fit Rute`, dan popup informasi lengkap pada setiap marker.
- Dukungan filter: Pencarian no. resi, filter kapal, filter negara, filter pelabuhan, dan filter status.

### 2. Aturan Perhitungan ETA & Delay Detection
Platform menghitung keterlambatan secara deterministik berdasarkan jadwal resmi:
- **Baseline Perhitungan:**
  - Jika `DELIVERED`: delay dihitung dari `actual_arrival` terhadap `expected_arrival`.
  - Jika aktif (`CREATED`, `BOOKED`, `PICKED_UP`, `IN_TRANSIT`): delay dihitung dari `estimated_arrival` (atau waktu saat ini jika telah melewati batas jadwal) terhadap `expected_arrival`.
- **Klasifikasi Keterlambatan (System Setting Thresholds):**
  - **LOW:** Keterlambatan `> 0` hingga `6.0` jam.
  - **MEDIUM:** Keterlambatan `> 6.0` hingga `24.0` jam.
  - **HIGH:** Keterlambatan `> 24.0` jam.
- **Pemisahan Cuaca vs Delay:** Kondisi cuaca (dari Open-Meteo) diperlakukan sebagai informasi operasional maritim (*Weather Advisory*), bukan klaim kausalitas langsung terhadap rumus delay.

### 3. Append-Only Tracking History (Integritas Data)
- Seluruh histori pelayaran kontainer dan pencatatan waypoint bersifat *append-only* (tidak dapat diubah atau dihapus sembarangan).
- Transisi status diverifikasi melalui state machine ketat untuk mencegah status melompat tidak wajar atau kembali ke masa lalu.

### 4. Dokumentasi Integrasi Telemetri AIS (Jujur & Transparan)
> **Catatan Kepatuhan Scope:** Sesuai batasan proyek akademik, pelacakan GPS satelit fisik real-time berada di luar cakupan perangkat keras. Oleh karena itu, data posisi AIS kapal disimulasikan secara sintetis melalui integrasi REST API yang valid (`POST /api/integrations/vessel-positions`) atau script pembantu `python/ais_collector.py`. Status sinkronisasi pada dashboard dan peta mencerminkan waktu pembaruan aktual dari database (`Vessel::max('last_position_at')`).

---

## 🚀 Panduan Menjalankan & Demo Pengujian

### 1. Kredensial Login Demo
| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@cargotrace.com` | `password` |
| **Logistics Operator** | `operator@cargotrace.com` | `password` |
| **Customer** | `customer@cargotrace.com` | `password` |

### 2. URL Halaman Utama
- **Dashboard Operasional:** `http://127.0.0.1:8000/dashboard`
- **Peta Pelacakan Maritim (Tracking Map):** `http://127.0.0.1:8000/tracking-map`
- **Export Laporan CSV:** `http://127.0.0.1:8000/tracking-map/export?code=CT-20261109-0002`

### 3. Menjalankan Automated Test Suite
Untuk memverifikasi 121 pengujian unit, integrasi API, state machine, dan anti-IDOR:
```bash
php artisan test
```

### 4. Menjalankan Python AIS Data Collector (Opsional)
```bash
python python/ais_collector.py --url http://127.0.0.1:8000 --email operator@cargotrace.com --password password --once
```
Atau melalui tombol simulasi di halaman Tracking Map:
- Klik tombol **"⚡ Simulasi Pergerakan Kapal (AIS Ping)"** pada panel kontrol peta.
