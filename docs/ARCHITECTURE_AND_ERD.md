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

## 2. Entity-Relationship Diagram (ERD)

The database schema models global maritime logistics operations with strict foreign key constraints, composite indexing for performant search, and immutable historical tables.

```mermaid
erDiagram
    USERS ||--o{ SHIPMENTS : "places/owns as customer"
    USERS ||--o{ TRACKING_EVENTS : "records"
    USERS ||--o{ EXCEPTIONS : "reported_by / resolved_by"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AUDIT_LOGS : "performs action"

    CARRIERS ||--o{ SHIPMENTS : "operates"
    CARRIERS ||--o{ VESSELS : "owns fleet"

    VESSELS ||--o{ SHIPMENTS : "transports"

    LOCATIONS ||--o{ SHIPMENTS : "origin_port"
    LOCATIONS ||--o{ SHIPMENTS : "destination_port"
    LOCATIONS ||--o{ TRACKING_EVENTS : "event_location"

    SHIPMENTS ||--o{ TRACKING_EVENTS : "has history"
    SHIPMENTS ||--o{ EXCEPTIONS : "encounters"

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role "ADMIN | OPERATOR | CUSTOMER"
        timestamp email_verified_at
        timestamps created_at_updated_at
    }

    CARRIERS {
        bigint id PK
        string code UK "e.g. MSK, EMC, MSC"
        string name
        string contact_email
        string contact_phone
        boolean is_active
        timestamps created_at_updated_at
    }

    VESSELS {
        bigint id PK
        bigint carrier_id FK
        string imo_number UK "e.g. IMO9811000"
        string name
        decimal last_latitude
        decimal last_longitude
        decimal last_speed_knots
        decimal last_heading_degrees
        string status "UNDERWAY | MOORED | ANCHORED"
        timestamp last_ais_sync
        timestamps created_at_updated_at
    }

    LOCATIONS {
        bigint id PK
        string un_locode UK "e.g. IDJKT, SGSIN, NLRTM"
        string name
        string type "PORT | AIRPORT | WAREHOUSE"
        string city
        string country "ISO 2-letter"
        decimal latitude
        decimal longitude
        string timezone
        timestamps created_at_updated_at
    }

    SHIPMENTS {
        bigint id PK
        string tracking_number UK "e.g. CTG-2026-0001"
        string container_number "ISO 6346 standard"
        bigint customer_id FK
        bigint carrier_id FK
        bigint vessel_id FK
        bigint origin_location_id FK
        bigint destination_location_id FK
        string cargo_type
        decimal weight_kg
        decimal volume_cbm
        decimal total_declared_value
        string current_status "CREATED | BOOKED | PICKED_UP | IN_TRANSIT | DELIVERED | CANCELLED"
        datetime expected_departure
        datetime actual_departure
        datetime expected_arrival
        datetime estimated_arrival
        datetime actual_arrival
        decimal delay_hours
        string delay_severity "ON_TIME | LOW | MEDIUM | HIGH"
        timestamps created_at_updated_at
        timestamp deleted_at "Soft Delete"
    }

    TRACKING_EVENTS {
        bigint id PK
        bigint shipment_id FK
        bigint location_id FK
        bigint created_by_user_id FK
        string status "Event Type"
        decimal latitude
        decimal longitude
        datetime event_time
        string description
        timestamps created_at_updated_at
    }

    EXCEPTIONS {
        bigint id PK
        bigint shipment_id FK
        bigint reported_by_user_id FK
        bigint resolved_by_user_id FK
        string exception_type "WEATHER | CUSTOMS_HOLD | PORT_CONGESTION | MECHANICAL"
        string severity "LOW | MEDIUM | HIGH | CRITICAL"
        string status "OPEN | INVESTIGATING | RESOLVED"
        decimal estimated_delay_hours
        text description
        text resolution_notes
        timestamp resolved_at
        timestamps created_at_updated_at
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string title
        text message
        string type "INFO | WARNING | ALERT | SUCCESS"
        boolean is_read
        timestamp read_at
        timestamps created_at_updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string action "LOGIN | CREATE_SHIPMENT | TRANSITION_STATUS | RESOLVE_EXCEPTION"
        string entity_type
        bigint entity_id
        text old_values
        text new_values
        string ip_address
        string user_agent
        timestamps created_at_updated_at
    }
```

---

## 3. Data Dictionary

### Table: `shipments`
Main operational ledger storing container bookings, voyages, monetary valuations, and automated delay metrics.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Auto-increment primary key |
| `tracking_number` | `VARCHAR(64)` | No | UNIQUE | Public tracking identifier (e.g. `CTG-2026-0001`) |
| `container_number` | `VARCHAR(32)` | Yes | INDEX | ISO 6346 container serial (e.g. `MSKU8291024`) |
| `customer_id` | `BIGINT UNSIGNED` | No | FK | References `users.id` (Customer owner) |
| `carrier_id` | `BIGINT UNSIGNED` | No | FK | References `carriers.id` |
| `vessel_id` | `BIGINT UNSIGNED` | Yes | FK | References `vessels.id` |
| `origin_location_id` | `BIGINT UNSIGNED` | No | FK | References `locations.id` (Port of Loading) |
| `destination_location_id` | `BIGINT UNSIGNED` | No | FK | References `locations.id` (Port of Discharge) |
| `cargo_type` | `VARCHAR(255)` | No | - | Goods commodity description |
| `weight_kg` | `DECIMAL(12,2)` | No | - | Total gross weight in kilograms |
| `volume_cbm` | `DECIMAL(10,2)` | No | - | Cubic meters volume capacity |
| `total_declared_value` | `DECIMAL(14,2)` | No | - | Declared valuation in USD for customs and insurance |
| `current_status` | `VARCHAR(32)` | No | INDEX | Current state (`CREATED`, `BOOKED`, `PICKED_UP`, `IN_TRANSIT`, `DELIVERED`, `CANCELLED`) |
| `expected_departure` | `DATETIME` | No | - | Initial contractual departure date/time |
| `actual_departure` | `DATETIME` | Yes | - | Verified departure date/time from origin terminal |
| `expected_arrival` | `DATETIME` | No | - | Initial contractual arrival schedule |
| `estimated_arrival` | `DATETIME` | No | - | Dynamic computed arrival based on current AIS speed/delays |
| `actual_arrival` | `DATETIME` | Yes | - | Verified berthing timestamp at destination port |
| `delay_hours` | `DECIMAL(8,2)` | No | - | Computed deviation in hours from `expected_arrival` |
| `delay_severity` | `VARCHAR(32)` | No | INDEX | Categorization: `ON_TIME`, `LOW`, `MEDIUM`, `HIGH` |
| `created_at` / `updated_at` | `TIMESTAMP` | Yes | - | Standard Laravel audit timestamps |
| `deleted_at` | `TIMESTAMP` | Yes | - | Soft delete timestamp |

---

### Table: `tracking_events`
Append-only chronological history of every physical and operational milestone encountered by the shipment.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Primary key |
| `shipment_id` | `BIGINT UNSIGNED` | No | FK | References `shipments.id` (Cascades on delete) |
| `location_id` | `BIGINT UNSIGNED` | Yes | FK | References `locations.id` |
| `created_by_user_id` | `BIGINT UNSIGNED` | Yes | FK | References `users.id` (User who recorded milestone) |
| `status` | `VARCHAR(32)` | No | - | Milestone type (e.g. `IN_TRANSIT`, `BERTHED`, `CUSTOMS_CLEARED`) |
| `latitude` | `DECIMAL(10,7)` | Yes | - | AIS/GPS latitude of the event |
| `longitude` | `DECIMAL(10,7)` | Yes | - | AIS/GPS longitude of the event |
| `event_time` | `DATETIME` | No | INDEX | Exact timestamp when physical event occurred |
| `description` | `TEXT` | No | - | Human-readable explanation of the milestone |
| `created_at` / `updated_at` | `TIMESTAMP` | Yes | - | Immutability timestamps |

---

### Table: `locations`
Master registry of 50 premier global container ports mapped with official UN/LOCODE codes and precise GPS coordinates.

| Column | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK | Primary key |
| `un_locode` | `VARCHAR(10)` | No | UNIQUE | 5-letter UN/LOCODE (e.g. `IDJKT`, `SGSIN`, `NLRTM`, `USLAX`) |
| `name` | `VARCHAR(255)` | No | - | Full official port or terminal name |
| `type` | `VARCHAR(32)` | No | - | Location type: `PORT`, `AIRPORT`, `INLAND_CONTAINER_DEPOT` |
| `city` | `VARCHAR(128)` | No | - | Municipality / Port city |
| `country` | `VARCHAR(2)` | No | INDEX | ISO 3166-1 alpha-2 country code (`ID`, `SG`, `NL`, `US`, etc.) |
| `latitude` | `DECIMAL(10,7)` | No | - | GPS Latitude coordinates |
| `longitude` | `DECIMAL(10,7)` | No | - | GPS Longitude coordinates |
| `timezone` | `VARCHAR(64)` | No | - | IANA timezone string (e.g. `Asia/Jakarta`, `Europe/Amsterdam`) |

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
