# BLUEPRINT SISTEM CARGOTRACE
## Global Maritime Shipment Visibility & Automated Tracking Platform
**Fokus Spesifik:** Pengiriman Barang Internasional Jalur Laut (Maritime Shipping Only)  
**Arsitektur:** Laravel 12 (Core Platform) + Python (External Data Collector & Processor) + MariaDB + Leaflet.js + Tailwind CSS  
**Target Proyek:** Final Project – Web Programming 2 · UAS 2026  
**Lokasi Proyek:** `D:\CargoTrace`  

---

## 1. Identitas dan Konsep Sistem

### 1.1 Deskripsi
CargoTrace adalah sistem informasi berbasis web yang dirancang khusus untuk memantau, mengelola, dan menganalisis aktivitas pengiriman barang internasional melalui jalur laut (maritime shipping). Sistem mengintegrasikan informasi kapal kargo (vessels), pelabuhan dunia (ports), koordinat perjalanan laut, kondisi cuaca perairan (marine weather), estimasi waktu kedatangan (ETA), serta gangguan pengiriman (exceptions).

Sistem menggunakan arsitektur gabungan:
- **Python**: Bertindak sebagai pengumpul, validator, dan pengolah data eksternal (AIS vessel tracking, marine weather, dan exchange rates).
- **Laravel 12**: Bertindak sebagai core platform yang mengelola database, business rules, state machine, REST API, autentikasi berbasis role (Sanctum), notifikasi, audit log, serta visualisasi dashboard dan peta.

### 1.2 Tujuan Sistem
1. Memantau pengiriman barang internasional melalui moda transportasi laut secara terpusat.
2. Mengintegrasikan master data kapal (IMO, MMSI, Bendera) dan pelabuhan (UN/LOCODE).
3. Mengambil dan menyinkronkan data eksternal secara otomatis via Python Data Collector.
4. Memperbarui posisi koordinat kapal dan checkpoint pengiriman secara real-time/otomatis.
5. Menghitung estimasi waktu kedatangan (ETA) dan mendeteksi keterlambatan berbasis aturan deterministik.
6. Memantau kondisi cuaca rute laut dan pelabuhan transit.
7. Mendukung multi-country dan multi-currency (konversi kurs referensi via Frankfurter API).
8. Menyediakan transparansi pelacakan kepada Customer dan dashboard kontrol bagi Operator serta Admin.

---

## 2. Ruang Lingkup Sistem

### 2.1 Batasan Transportasi
- **Objek Transportasi:** Kapal kargo kontainer (Container Vessels / Bulk Carriers).
- **Lokasi & Node:** Pelabuhan internasional (Origin, Transit, Destination Ports) dan jalur perairan laut (Maritime Routes & Waypoints).
- **Cakupan Barang:** Beragam jenis kargo legal (elektronik, pakaian, mesin industri, bahan makanan, peralatan, komoditas).
- **Batasan Eksplisit:** **Tidak melacak penerbangan (Air) atau transportasi darat (Land).**

---

## 3. User Roles dan Hak Akses

| Hak Akses / Fitur | Administrator | Logistics Operator | Customer |
| :--- | :---: | :---: | :---: |
| **Maritime Dashboard** | Full Global Analytics | Operasional Wilayah | Terbatas (Milik Sendiri) |
| **User & Role Management** | ✓ | – | – |
| **Carrier & Vessel Management** | ✓ (Kelola Kapal & Carrier) | Read / Assign Kapal | – |
| **Port Management (Pelabuhan)** | ✓ (Kelola Pelabuhan) | Read / Monitoring | – |
| **Shipment Management** | ✓ | ✓ | – |
| **Tracking & AIS Updates** | ✓ | ✓ (Koreksi Manual) | Read (Kapal & Kargonya) |
| **Exception Management** | ✓ | ✓ | Read (Terkait Barangnya) |
| **Document Management** | ✓ | ✓ | Download (Dokumen Miliknya) |
| **Audit Log & API Sync Monitor** | ✓ | Sesuai Izin | – |

---

## 4. Modul dan Fitur Utama Maritim

1. **Maritime Shipment Management**: Pengelolaan data shipment kargo laut, nomor kontainer, kapal yang ditugaskan, pelabuhan muat/bongkar, berat/volume, dan nilai kargo.
2. **Vessel Management**: Master data kapal kargo (Nama kapal, IMO number, MMSI, Bendera negara, tipe kapal, kapasitas TEU, posisi terakhir, status berlayar).
3. **Port Management**: Master data pelabuhan internasional berbasis UN/LOCODE (Port of Tanjung Priok `IDTPP`, Singapore `SGSIN`, Rotterdam `NLRTM`, Shanghai `CNSHA`, Hamburg `DEHAM`, Tokyo `JPTYO`, dll.).
4. **Automatic Data Collection (Python)**: Script Python mandiri untuk mengumpulkan data AIS dan cuaca laut, memvalidasi integritas data, lalu mengirimkannya ke endpoint integrasi Laravel.
5. **Real-time Vessel Tracking**: Visualisasi posisi kapal di peta perairan dunia dengan Leaflet.js + OpenStreetMap.
6. **Maritime Shipment Timeline**:
   `CREATED` $\rightarrow$ `BOOKED` $\rightarrow$ `LOADED` $\rightarrow$ `DEPARTED` $\rightarrow$ `IN_TRANSIT` $\rightarrow$ `ARRIVED_AT_PORT` $\rightarrow$ `UNLOADED` $\rightarrow$ `DELIVERED`.
7. **ETA & Delay Detection**: Kalkulasi keterlambatan terhadap jadwal sandar pelabuhan tujuan dengan klasifikasi severity:
   - `0 - 6 jam`: **LOW**
   - `> 6 - 24 jam`: **MEDIUM**
   - `> 24 jam`: **HIGH**
8. **Marine Weather Monitoring**: Integrasi Open-Meteo untuk data kecepatan angin, suhu udara permukaan laut, dan kondisi cuaca sepanjang rute pelayaran.
9. **Maritime Exception Management**:
   `WEATHER_DELAY`, `PORT_CONGESTION`, `CUSTOMS_HOLD`, `VESSEL_CHANGE`, `ROUTE_DEVIATION`, `DAMAGED`, `DOCUMENTATION_ISSUE`, `VESSEL_DELAY`.
10. **Multi-Country & Multi-Currency**: Kurs konversi referensi (USD, EUR, IDR, SGD, JPY) berbasis Frankfurter API.
11. **Document Management**: Pengelolaan dokumen maritim standar (Bill of Lading / B/L, Commercial Invoice, Packing List, Certificate of Origin, Delivery Order).
12. **Operational Dashboard (Chart.js)**: KPI pelayaran, distribusi status, volume per ocean carrier, delay rate, dan monitor sinkronisasi API.

---

## 5. Arsitektur Komponen

```
[ EXTERNAL SOURCES: AIS Data · Marine Weather (Open-Meteo) · Currencies (Frankfurter) ]
                                      │
                    [ PYTHON DATA COLLECTOR ENGINE ]
             (Fetch · Validate · Format · Change Detection)
                                      │  (HTTP / Bearer Token)
                        [ LARAVEL 12 CORE PLATFORM ]
                 Controllers · Services · Policies · Reverb
                                      │
                        [ MARIADB / MYSQL STORAGE ]
              Shipments · Vessels · Ports · Positions · Exceptions
                                      │
                   [ WEB & API FRONTEND INTERFACES ]
            Leaflet.js Maritime Map · Chart.js Analytics · REST API
```

---

## 6. Rencana Penyesuaian Database (Tanpa Reset)

Data yang sudah ada dipertahankan dan dispesifikkan ke ranah maritim:
1. **`carriers`**: Difokuskan pada Ocean Carriers (A.P. Moller - Maersk, Evergreen Marine, MSC, CMA CGM, ONE).
2. **`locations` $\rightarrow$ `ports`**: Memastikan seluruh lokasi adalah pelabuhan maritim dengan kode UN/LOCODE dan koordinat perairan yang akurat.
3. **Tabel Baru `vessels`**: Menyimpan data armada kapal kargo (`imo_number`, `mmsi`, `name`, `flag`, `carrier_id`, dll.).
4. **Tabel Baru `vessel_positions`**: Riwayat koordinat posisi kapal yang diperbarui secara otomatis.
5. **Relasi `shipments`**: Menghubungkan shipment ke `vessel_id` dan `container_number`.
