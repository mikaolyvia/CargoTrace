# CargoTrace — Architecture & Entity-Relationship Documentation (ERD)

This document provides the complete technical architectural design, domain model, database schema, data dictionary, and security specifications for the **CargoTrace Global Maritime Shipment Visibility & Automated Tracking Platform**, in full compliance with the Web Programming 2 final project brief.

---

## 1. System Architecture Diagram

CargoTrace adheres to a clean, decoupled multi-tier Laravel architecture leveraging the **Controller-Service-Repository** design pattern. It enforces strict separation of concerns between HTTP presentation, domain logic, data persistence, and external third-party integrations.

```mermaid
flowchart TD
    subgraph Clients["Presentation & Client Tier"]
        Browser["Blade Web UI + Tailwind CSS\n(Browser Client)"]
        MapClient["Leaflet.js + OpenStreetMap\n(Interactive Map)"]
        Postman["Postman API Client / SPA / Mobile\n(REST Consumer)"]
        TelemetryScript["AIS Telemetry Collector\n(Python Script / Scheduler)"]
    end

    subgraph Security["Security & Middleware Gateway"]
        CSRF["CSRF Protection\n(Web Routes)"]
        Sanctum["Laravel Sanctum\n(Bearer Token Auth)"]
        RBAC["Role-Based Access Control\n(Admin / Operator / Customer)"]
    end

    subgraph Controllers["HTTP Controller Tier"]
        WebControllers["Web Controllers\n(Dashboard, Shipments, Map, Auth, Audit, Notif)"]
        ApiControllers["API Controllers\n(ShipmentApi, TrackingApi, ExceptionApi, MasterApi)"]
    end

    subgraph DomainServices["Service & Business Logic Tier"]
        ShipmentService["ShipmentService\n(Lifecycle & State Machine)"]
        EtaService["EtaCalculationService\n(Deterministic Delay Rule)"]
        WeatherService["WeatherService\n(Open-Meteo Integration)"]
        CurrencyService["CurrencyService\n(Frankfurter Multi-Currency)"]
        TelemetryService["TelemetryService\n(Vessel AIS Sync)"]
        AuditService["AuditLogService\n(Append-Only Logging)"]
    end

    subgraph DataTier["Data Persistence Tier (MySQL / MariaDB / SQLite)"]
        UsersTable[("users")]
        ShipmentsTable[("shipments")]
        TrackingEventsTable[("tracking_events (Append-Only)")]
        ExceptionsTable[("exceptions")]
        LocationsTable[("locations (50 Global Ports)")]
        CarriersTable[("carriers")]
        VesselsTable[("vessels")]
        NotifTable[("notifications")]
        AuditTable[("audit_logs")]
    end

    subgraph ExternalApis["External Services & APIs"]
        OpenMeteo["Open-Meteo API\n(Live Port Marine Weather)"]
        Frankfurter["Frankfurter FX API\n(Customs Valuation Rates)"]
    end

    Browser --> CSRF --> WebControllers
    MapClient --> CSRF --> WebControllers
    Postman --> Sanctum --> RBAC --> ApiControllers
    TelemetryScript --> Sanctum --> ApiControllers

    WebControllers --> DomainServices
    ApiControllers --> DomainServices

    DomainServices --> DataTier
    WeatherService -.-> OpenMeteo
    CurrencyService -.-> Frankfurter
```

---

## 2. Entity-Relationship Diagrams (ERD)

Untuk menjaga keterbacaan (*readability*) dan fokus arsitektur sistem pada laporan UAS, skema basis data dibagi menjadi dua diagram:
1. **ERD 1 — Core CargoTrace (12 Tabel Domain Aplikasi)**: Diagram utama yang merepresentasikan rancangan proses bisnis logistik maritim.
2. **ERD 2 — Laravel Infrastructure (9 Tabel Pendukung Framework)**: Diagram pendukung untuk layanan internal framework Laravel (auth tokens, sessions, job queue, caching, migrations).

---

### 2.1 ERD 1 — Core CargoTrace (Domain Utama — 12 Tabel Aplikasi)

Diagram ini adalah diagram ERD relasional fisik (*Physical Database ERD*) aktual hasil audit migrasi Laravel. Tabel `SHIPMENTS` diposisikan sebagai entitas sentral (*central aggregate root*), dikelilingi oleh entitas master data (`USERS`, `CARRIERS`, `VESSELS`, `LOCATIONS`) dan entitas transaksional detail (`SHIPMENT_ITEMS`, `TRACKING_EVENTS`, `SHIPMENT_EXCEPTIONS`, `VESSEL_POSITIONS`, `NOTIFICATIONS`, `AUDIT_LOGS`, `SYSTEM_SETTINGS`).

Setiap garis relasi dilengkapi dengan:
- **Simbol Crow's Foot Fisik**: `||` (Exactly 1), `|o` (0 or 1), `o{` (0 or Many), `|{` (1 or Many).
- **Anotasi Kardinalitas Teks**: Menampilkan jumlah rasio di kedua ujung relasi (misal `1 : 0..N` atau `0..1 : 0..N`) beserta nama Foreign Key.

```mermaid
erDiagram
    %% =============================================================
    %% 1. RELASI MASTER DATA KE ENTITAS PUSAT (SHIPMENTS)
    %% =============================================================
    USERS ||--o{ SHIPMENTS : "1 : 0..N [customer_id]"
    USERS ||--o{ SHIPMENTS : "1 : 0..N [created_by]"
    CARRIERS ||--o{ SHIPMENTS : "1 : 0..N [carrier_id]"
    VESSELS |o--o{ SHIPMENTS : "0..1 : 0..N [vessel_id]"
    LOCATIONS ||--o{ SHIPMENTS : "1 : 0..N [origin_id]"
    LOCATIONS ||--o{ SHIPMENTS : "1 : 0..N [destination_id]"
    LOCATIONS |o--o{ SHIPMENTS : "0..1 : 0..N [current_location_id]"

    %% =============================================================
    %% 2. RELASI ENTITAS PUSAT KE TRANSAKSIONAL DETAIL
    %% =============================================================
    SHIPMENTS ||--|{ SHIPMENT_ITEMS : "1 : 1..N [shipment_id]"
    SHIPMENTS ||--o{ TRACKING_EVENTS : "1 : 0..N [shipment_id]"
    SHIPMENTS ||--o{ SHIPMENT_EXCEPTIONS : "1 : 0..N [shipment_id]"

    %% =============================================================
    %% 3. RELASI SPESIFIK & OPERASIONAL LAINNYA
    %% =============================================================
    CARRIERS |o--o{ VESSELS : "0..1 : 0..N [carrier_id]"
    VESSELS ||--o{ VESSEL_POSITIONS : "1 : 0..N [vessel_id]"
    LOCATIONS |o--o{ TRACKING_EVENTS : "0..1 : 0..N [location_id]"
    USERS ||--o{ TRACKING_EVENTS : "1 : 0..N [created_by]"
    TRACKING_EVENTS |o--o{ SHIPMENT_EXCEPTIONS : "0..1 : 0..N [tracking_event_id]"
    USERS ||--o{ SHIPMENT_EXCEPTIONS : "1 : 0..N [reported_by]"
    USERS |o--o{ SHIPMENT_EXCEPTIONS : "0..1 : 0..N [resolved_by]"
    USERS ||--o{ NOTIFICATIONS : "1 : 0..N [user_id]"
    USERS |o--o{ AUDIT_LOGS : "0..1 : 0..N [user_id]"

    %% =============================================================
    %% DEFINISI TABEL & ATRIBUT LENGKAP (SESUAI MIGRATION AKTUAL)
    %% =============================================================

    USERS {
        bigint id PK
        varchar_150 name
        varchar_191 email UK
        timestamp email_verified_at "nullable"
        varchar_255 password
        enum role "ADMIN, OPERATOR, CUSTOMER (idx)"
        varchar_30 phone "nullable"
        varchar_100 remember_token "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    CARRIERS {
        bigint id PK
        varchar_20 code UK
        varchar_150 name "INDEX"
        varchar_191 contact_email "nullable"
        varchar_50 contact_phone "nullable"
        boolean is_active "INDEX, def: true"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        timestamp deleted_at "softDeletes, nullable"
    }

    VESSELS {
        bigint id PK
        bigint carrier_id FK "nullable, INDEX"
        varchar_150 name
        varchar_20 imo_number UK "INDEX"
        varchar_20 mmsi UK "nullable, INDEX"
        varchar_100 flag_country "def: Panama"
        varchar_50 vessel_type "def: CONTAINER_SHIP"
        unsigned_int capacity_teu "nullable"
        varchar_50 status "INDEX, def: UNDERWAY"
        decimal_10_7 last_latitude "nullable"
        decimal_10_7 last_longitude "nullable"
        decimal_5_2 last_speed_knots "nullable"
        decimal_5_2 last_heading_degrees "nullable"
        timestamp last_position_at "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        timestamp deleted_at "softDeletes, nullable"
    }

    VESSEL_POSITIONS {
        bigint id PK
        bigint vessel_id FK "INDEX: [vessel_id, recorded_at]"
        decimal_10_7 latitude
        decimal_10_7 longitude
        decimal_5_2 speed_knots "nullable"
        decimal_5_2 heading_degrees "nullable"
        varchar_50 status "def: UNDERWAY"
        varchar_50 source "def: AIS_FEED"
        timestamp recorded_at "INDEX"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    LOCATIONS {
        bigint id PK
        varchar_20 code UK "UN/LOCODE e.g. IDJKT"
        varchar_150 name
        enum type "PORT, AIRPORT, WAREHOUSE, HUB, CUSTOMS"
        varchar_100 city "INDEX"
        varchar_100 country "INDEX"
        varchar_3 currency_code "nullable"
        varchar_10 currency_symbol "nullable"
        decimal_10_7 latitude
        decimal_10_7 longitude
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        timestamp deleted_at "softDeletes, nullable"
    }

    SHIPMENTS {
        bigint id PK
        varchar_50 shipment_number UK "Tracking ID e.g. SHP-2026-0001"
        varchar_50 container_number "nullable, INDEX (ISO 6346)"
        bigint customer_id FK "users.id, restrictOnDelete"
        bigint created_by FK "users.id, restrictOnDelete"
        bigint carrier_id FK "carriers.id, restrictOnDelete"
        bigint vessel_id FK "vessels.id, nullable, nullOnDelete"
        bigint origin_id FK "locations.id, restrictOnDelete"
        bigint destination_id FK "locations.id, restrictOnDelete"
        bigint current_location_id FK "locations.id, nullable, nullOnDelete"
        varchar_100 cargo_type
        decimal_10_2 total_weight_kg "def: 0.00"
        decimal_10_2 total_volume_cbm "def: 0.00"
        decimal_14_2 total_declared_value "def: 0.00"
        varchar_3 currency "def: USD"
        enum current_status "CREATED, BOOKED, PICKED_UP, IN_TRANSIT, DELIVERED"
        datetime expected_departure
        datetime expected_arrival "INDEX"
        datetime estimated_arrival "INDEX"
        datetime actual_arrival "nullable"
        boolean is_delayed "INDEX, def: false"
        decimal_8_2 delay_hours "def: 0.00"
        enum delay_severity "NONE, LOW, MEDIUM, HIGH (idx)"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        timestamp deleted_at "softDeletes, nullable"
    }

    SHIPMENT_ITEMS {
        bigint id PK
        bigint shipment_id FK "cascadeOnDelete, INDEX"
        varchar_200 item_name
        varchar_30 hs_code "nullable"
        unsigned_int quantity "def: 1"
        decimal_8_2 weight_kg "def: 0.00"
        decimal_12_2 unit_price "def: 0.00"
        decimal_14_2 total_price "def: 0.00"
        decimal_8_2 length_cm "nullable"
        decimal_8_2 width_cm "nullable"
        decimal_8_2 height_cm "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    TRACKING_EVENTS {
        bigint id PK
        bigint shipment_id FK "restrictOnDelete, INDEX"
        bigint location_id FK "locations.id, nullable, nullOnDelete"
        enum event_type "ORDER_CREATED, BOOKING_CONFIRMED, PICKED_UP, DEPARTED, IN_TRANSIT, ARRIVED, DELIVERED, EXCEPTION"
        datetime event_time "INDEX: [shipment_id, event_time]"
        decimal_10_7 latitude "nullable"
        decimal_10_7 longitude "nullable"
        text description
        varchar_100 weather_condition "nullable"
        decimal_5_2 weather_temp_c "nullable"
        bigint created_by FK "users.id, restrictOnDelete"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    SHIPMENT_EXCEPTIONS {
        bigint id PK
        bigint shipment_id FK "restrictOnDelete, INDEX"
        bigint tracking_event_id FK "nullable, nullOnDelete"
        enum exception_type "CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE"
        enum severity "LOW, MEDIUM, HIGH, CRITICAL"
        enum status "OPEN, INVESTIGATING, RESOLVED (idx)"
        decimal_8_2 impact_hours "def: 0.00"
        text description
        datetime detected_at "INDEX"
        datetime resolved_at "nullable"
        text resolution_notes "nullable"
        bigint reported_by FK "users.id, restrictOnDelete"
        bigint resolved_by FK "users.id, nullable, nullOnDelete"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK "cascadeOnDelete, INDEX: [user_id, read_at]"
        varchar_150 title
        text message
        varchar_50 type "def: INFO (INFO, SUCCESS, WARNING, DANGER)"
        json data "nullable"
        timestamp read_at "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK "nullable, nullOnDelete, INDEX"
        varchar_100 action "INDEX"
        varchar_100 entity_type "INDEX: [entity_type, entity_id]"
        unsigned_bigint entity_id "nullable"
        json old_values "nullable"
        json new_values "nullable"
        varchar_45 ip_address "nullable"
        text user_agent "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    SYSTEM_SETTINGS {
        bigint id PK
        varchar_100 key UK
        varchar_255 value
        varchar_255 description "nullable"
        timestamp updated_at "nullable"
    }
```

---

### 2.2 UML Class Diagram (Logical Object Model ERD)

Representasi berorientasi objek (*Object-Oriented Data Model*) dengan format kotak 3-sekat (nama kelas, atribut kolom, dan method Eloquent) serta kardinalitas eksplisit di kedua ujung garis:

```mermaid
classDiagram
    direction TB

    class User {
        -bigint id [PK]
        -varchar(150) name
        -varchar(191) email [UK]
        -varchar(255) password
        -enum role "ADMIN, OPERATOR, CUSTOMER"
        -varchar(30) phone
        +register() bool
        +login() bool
        +isAdmin() bool
        +isOperator() bool
        +isCustomer() bool
    }

    class Carrier {
        -bigint id [PK]
        -varchar(20) code [UK]
        -varchar(150) name
        -varchar(191) contact_email
        -boolean is_active
        +activate() void
        +getActiveVessels() Collection
    }

    class Location {
        -bigint id [PK]
        -varchar(20) code [UK]
        -varchar(150) name
        -enum type "PORT, AIRPORT, HUB"
        -varchar(100) city
        -varchar(100) country
        -decimal(10,7) latitude
        -decimal(10,7) longitude
        +getCoordinates() array
        +isPort() bool
    }

    class Vessel {
        -bigint id [PK]
        -bigint carrier_id [FK]
        -varchar(150) name
        -varchar(20) imo_number [UK]
        -varchar(50) status
        -decimal(10,7) last_latitude
        -decimal(10,7) last_longitude
        +updateAisPosition() void
        +getLatestPosition() VesselPosition
    }

    class VesselPosition {
        -bigint id [PK]
        -bigint vessel_id [FK]
        -decimal(10,7) latitude
        -decimal(10,7) longitude
        -decimal(5,2) speed_knots
        -timestamp recorded_at
        +getLatLng() array
    }

    class Shipment {
        -bigint id [PK]
        -varchar(50) shipment_number [UK]
        -varchar(50) container_number
        -bigint customer_id [FK]
        -bigint carrier_id [FK]
        -bigint vessel_id [FK]
        -bigint origin_id [FK]
        -bigint destination_id [FK]
        -enum current_status
        -decimal(14,2) total_declared_value
        -decimal(8,2) delay_hours
        +recalculateTotals() void
        +transitionStatus(status) bool
        +calculateDelay() float
    }

    class ShipmentItem {
        -bigint id [PK]
        -bigint shipment_id [FK]
        -varchar(200) item_name
        -unsigned_int quantity
        -decimal(8,2) weight_kg
        -decimal(12,2) unit_price
        +calculateSubtotal() float
    }

    class TrackingEvent {
        -bigint id [PK]
        -bigint shipment_id [FK]
        -bigint location_id [FK]
        -enum event_type
        -datetime event_time
        -text description
        +hasCoordinates() bool
    }

    class ShipmentException {
        -bigint id [PK]
        -bigint shipment_id [FK]
        -enum exception_type
        -enum severity
        -enum status
        -decimal(8,2) impact_hours
        +resolve(notes, userId) bool
    }

    class Notification {
        -bigint id [PK]
        -bigint user_id [FK]
        -varchar(150) title
        -text message
        +markAsRead() bool
    }

    class AuditLog {
        -bigint id [PK]
        -bigint user_id [FK]
        -varchar(100) action
        -varchar(100) entity_type
        +logChange() void
    }

    class SystemSetting {
        -bigint id [PK]
        -varchar(100) key [UK]
        -varchar(255) value
        +getValue(key) mixed
        +setValue(key, val) void
    }

    User "1" --> "0..*" Shipment : customer_id
    User "1" --> "0..*" Shipment : created_by
    Carrier "1" --> "0..*" Shipment : carrier_id
    Vessel "0..1" --> "0..*" Shipment : vessel_id
    Location "1" --> "0..*" Shipment : origin_id
    Location "1" --> "0..*" Shipment : destination_id
    Location "0..1" --> "0..*" Shipment : current_location_id

    Shipment "1" *-- "1..*" ShipmentItem : shipment_id
    Vessel "1" *-- "0..*" VesselPosition : vessel_id

    Carrier "0..1" --> "0..*" Vessel : carrier_id
    Shipment "1" --> "0..*" TrackingEvent : shipment_id
    Shipment "1" --> "0..*" ShipmentException : shipment_id

    Location "0..1" --> "0..*" TrackingEvent : location_id
    User "1" --> "0..*" TrackingEvent : created_by
    TrackingEvent "0..1" --> "0..*" ShipmentException : tracking_event_id
    User "1" --> "0..*" ShipmentException : reported_by
    User "0..1" --> "0..*" ShipmentException : resolved_by

    User "1" --> "0..*" Notification : user_id
    User "0..1" --> "0..*" AuditLog : user_id
```

---

### 2.3 ERD 2 — Laravel Infrastructure (Framework & System Tables)

Diagram ini mendokumentasikan 9 tabel pendukung bawaan Laravel 11 untuk penanganan sesi web, autentikasi REST API (Sanctum), antrean background jobs (*queueing*), penyimpanan cache, reset kata sandi, dan migration history.

```mermaid
erDiagram
    USERS ||--o{ SESSIONS : "user_id (optional session owner)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "tokenable_id (Sanctum API tokens)"

    JOBS {
        bigint id PK
        string queue
        longtext payload
        tinyint attempts
        int reserved_at
        int available_at
        int created_at
    }

    JOB_BATCHES {
        string id PK
        string name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options
        int cancelled_at
        int created_at
        int finished_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    SESSIONS {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        int expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        int expiration
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamps created_at_updated_at
    }

    MIGRATIONS {
        int id PK
        string migration
        int batch
    }
```

---

## 3. Data Dictionary

### Table: `shipments`
Main operational ledger storing container shipments, assigned vessels, valuations, and automated delay metrics.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Auto-increment primary key |
| `shipment_number` | `VARCHAR(50)` | No | UNIQUE | Public tracking identifier (e.g. `SHP-2026-0001`) |
| `container_number` | `VARCHAR(50)` | Yes | INDEX | ISO 6346 container serial (e.g. `MSKU8291024`) |
| `customer_id` | `BIGINT UNSIGNED` | No | FK | References `users.id` (Customer who owns shipment) |
| `created_by` | `BIGINT UNSIGNED` | No | FK | References `users.id` (Operator who registered shipment) |
| `carrier_id` | `BIGINT UNSIGNED` | No | FK | References `carriers.id` |
| `vessel_id` | `BIGINT UNSIGNED` | Yes | FK | References `vessels.id` |
| `origin_id` | `BIGINT UNSIGNED` | No | FK | References `locations.id` (Port of Loading) |
| `destination_id` | `BIGINT UNSIGNED` | No | FK | References `locations.id` (Port of Discharge) |
| `current_location_id` | `BIGINT UNSIGNED` | Yes | FK | References `locations.id` (Last known checkpoint) |
| `cargo_type` | `VARCHAR(100)` | No | - | Goods commodity description |
| `total_weight_kg` | `DECIMAL(10,2)` | No | - | Total gross weight in kilograms |
| `total_volume_cbm` | `DECIMAL(10,2)` | No | - | Total volume capacity in cubic meters |
| `total_declared_value` | `DECIMAL(14,2)` | No | - | Declared valuation for customs and insurance |
| `currency` | `VARCHAR(3)` | No | - | ISO 4217 Currency Code (default `USD`) |
| `current_status` | `ENUM` | No | INDEX | `CREATED`, `BOOKED`, `PICKED_UP`, `IN_TRANSIT`, `DELIVERED` |
| `expected_departure` | `DATETIME` | No | - | Contractual departure schedule |
| `expected_arrival` | `DATETIME` | No | INDEX | Contractual arrival schedule |
| `estimated_arrival` | `DATETIME` | No | INDEX | Dynamic computed arrival based on AIS speed & delays |
| `actual_arrival` | `DATETIME` | Yes | - | Verified berthing timestamp at destination port |
| `is_delayed` | `TINYINT(1)` | No | INDEX | Boolean flag (1 if delayed, 0 if on-time) |
| `delay_hours` | `DECIMAL(8,2)` | No | - | Computed deviation in hours from `expected_arrival` |
| `delay_severity` | `ENUM` | No | INDEX | Categorization: `NONE`, `LOW`, `MEDIUM`, `HIGH` |
| `created_at` / `updated_at` | `TIMESTAMP` | Yes | - | Standard Laravel audit timestamps |
| `deleted_at` | `TIMESTAMP` | Yes | - | Soft delete timestamp |

---

### Table: `tracking_events`
Append-only chronological history of every physical and operational milestone encountered by the shipment.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Auto-increment primary key |
| `shipment_id` | `BIGINT UNSIGNED` | No | FK | References `shipments.id` (Restrict on delete) |
| `location_id` | `BIGINT UNSIGNED` | Yes | FK | References `locations.id` (Null on delete) |
| `event_type` | `ENUM` | No | INDEX | `ORDER_CREATED`, `BOOKING_CONFIRMED`, `PICKED_UP`, `DEPARTED`, `IN_TRANSIT`, `ARRIVED`, `DELIVERED`, `EXCEPTION` |
| `event_time` | `DATETIME` | No | INDEX | Timestamp when physical event occurred |
| `latitude` | `DECIMAL(10,7)` | Yes | - | GPS latitude coordinate |
| `longitude` | `DECIMAL(10,7)` | Yes | - | GPS longitude coordinate |
| `description` | `TEXT` | No | - | Human-readable explanation of milestone |
| `weather_condition` | `VARCHAR(100)` | Yes | - | Open-Meteo recorded weather status |
| `weather_temp_c` | `DECIMAL(5,2)` | Yes | - | Ambient temperature in Celsius |
| `created_by` | `BIGINT UNSIGNED` | No | FK | References `users.id` (User who recorded milestone) |
| `created_at` / `updated_at` | `TIMESTAMP` | Yes | - | Immutability timestamps |

---

### Table: `locations`
Master registry of global container ports and logistics hubs.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Auto-increment primary key |
| `code` | `VARCHAR(20)` | No | UNIQUE | UN/LOCODE identifier (e.g. `IDJKT`, `SGSIN`, `NLRTM`) |
| `name` | `VARCHAR(150)` | No | - | Full official port or hub name |
| `type` | `ENUM` | No | INDEX | `PORT`, `AIRPORT`, `WAREHOUSE`, `HUB`, `CUSTOMS` |
| `city` | `VARCHAR(100)` | No | INDEX | Municipality / Port city |
| `country` | `VARCHAR(100)` | No | INDEX | Country name |
| `currency_code` | `VARCHAR(3)` | Yes | - | Default port currency code (e.g. `IDR`, `SGD`, `EUR`) |
| `currency_symbol` | `VARCHAR(10)` | Yes | - | Currency display symbol (e.g. `Rp`, `S$`, `€`) |
| `latitude` | `DECIMAL(10,7)` | No | - | GPS Latitude coordinate |
| `longitude` | `DECIMAL(10,7)` | No | - | GPS Longitude coordinate |
| `created_at` / `updated_at` | `TIMESTAMP` | Yes | - | Standard audit timestamps |
| `deleted_at` | `TIMESTAMP` | Yes | - | Soft delete timestamp |

---

## 4. State Machine: Shipment Lifecycle

Status transitions follow strict state machine rules implemented in `ShipmentService.php`:

```mermaid
stateDiagram-v2
    [*] --> CREATED : Manifest Created
    CREATED --> BOOKED : Slot Confirmed with Carrier Line
    BOOKED --> PICKED_UP : Container Loaded at Shipper Yard
    PICKED_UP --> IN_TRANSIT : Vessel Departs Origin Port
    IN_TRANSIT --> DELIVERED : Container Discharged at Destination
    CREATED --> CANCELLED : Booking Revoked
    BOOKED --> CANCELLED : Carrier Booking Cancelled
    DELIVERED --> [*] : Terminal State (Read-Only)
    CANCELLED --> [*] : Terminal State (Read-Only)
```

- **Forward Determinism:** Status cannot jump backwards (e.g. `IN_TRANSIT` cannot return to `CREATED`).
- **Terminal Immutability:** Once marked `DELIVERED` or `CANCELLED`, the record enters a read-only terminal state.
- **Append-Only Tracking:** Every status transition automatically appends a new immutable row into `tracking_events`.

---

## 5. Security & Role-Based Access Control (RBAC) Matrix

| Feature / Resource | Administrator | Logistics Operator | Customer |
|---|:---:|:---:|:---:|
| View All Shipments | ✅ Yes | ✅ Yes | ❌ (Own Shipments Only) |
| Create New Shipment Manifest | ✅ Yes | ✅ Yes | ❌ (Forbidden) |
| Transition Shipment Status | ✅ Yes | ✅ Yes | ❌ (Forbidden) |
| Append Tracking Events | ✅ Yes | ✅ Yes | ❌ (Forbidden) |
| Report & Resolve Exceptions | ✅ Yes | ✅ Yes | ❌ (View-Only on Own Shipments) |
| Manage Master Data (Ports, Carriers) | ✅ Yes | ❌ (Read-Only) | ❌ (Read-Only) |
| Access Regulatory Audit Trail (`/audit-logs`) | ✅ Yes | ❌ (Forbidden 403) | ❌ (Forbidden 403) |
| Soft Delete Records | ✅ Yes | ❌ (Forbidden) | ❌ (Forbidden) |
| Mandatory Logout on Account Switching | ✅ Enforced | ✅ Enforced | ✅ Enforced |
