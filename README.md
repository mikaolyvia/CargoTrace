# CargoTrace — Global Maritime Shipment Visibility & Automated Tracking Platform

> **Web Programming 2 • Final Project (UAS) Portfolio Submission**  
> An enterprise-grade, multi-tenant maritime logistics platform engineered with **Laravel 12**, **MySQL / MariaDB**, **Leaflet.js**, **Chart.js**, **Tailwind CSS**, and **Open-Meteo API**. CargoTrace delivers real-time container tracking, automated deterministic delay analysis (ETA), an immutable audit trail, and role-based multi-tenant data isolation across 50 premier world container ports.

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Automated Tests](https://img.shields.io/badge/Tests-134%20Passed-brightgreen.svg)]()
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Postman](https://img.shields.io/badge/Postman-v2.1%20Collection-orange.svg)](CargoTrace.postman_collection.json)

---

## 1. Problem Statement & Solution

### The Problem
International ocean container shipping is plagued by fragmented data silos, opaque tracking timelines, and delayed notifications:
- **Scattered Information:** Shippers, operators, and customers must consult disjointed shipping line websites, port terminal portals, and manual spreadsheets.
- **Uncertainty for Customers:** B2B cargo owners are left unaware of where their containers are, when customs holds occur, or when actual arrival times (ETA) drift.
- **Operational Blindspots:** Dispatchers and logistics operators lack centralized tooling to triage vessel anomalies, severe weather disruptions, and berth congestion before they cause contractual penalties.

### The CargoTrace Solution
CargoTrace centralizes global ocean freight visibility into a single, reliable system:
1. **End-to-End Visibility:** Tracks international ocean shipments across 50 premier global container ports (Asia, Europe, Middle East, Americas, Oceania, Africa) utilizing UN/LOCODE standards.
2. **Deterministic Delay Detection:** Automatically calculates schedule drift (`actual_arrival` or `estimated_arrival` vs. `expected_arrival`) and classifies severity into `ON_TIME`, `LOW`, `MEDIUM`, and `HIGH`.
3. **Multi-Tenant Data Isolation (RBAC):** Customers strictly see only their own shipments, protecting commercial privacy and preventing Insecure Direct Object References (IDOR).
4. **Append-Only Immutability:** Tracking events and regulatory audit logs are immutable, ensuring tamper-proof compliance.
5. **Interactive Marine Telemetry:** Leaflet.js interactive maps with real-time weather advisories powered by Open-Meteo and vessel AIS simulation.

---

## 2. Key Features

- **Shipment Lifecycle State Machine:** Enforces strict transition business rules (`CREATED` &rarr; `BOOKED` &rarr; `PICKED_UP` &rarr; `IN_TRANSIT` &rarr; `DELIVERED`). Terminal states (`DELIVERED`, `CANCELLED`) are locked against unauthorized retroactive edits.
- **Interactive Maritime Tracking Map (`/tracking-map`):** High-performance Leaflet.js map plotting origin, transit, and destination ports, active ocean vessel coordinates, heading, speed knots, and geodesic maritime voyage routes.
- **50 World Premier Container Ports:** Master data seeded with authentic UN/LOCODE codes, GPS coordinates, and timezones covering Shanghai, Singapore, Rotterdam, Los Angeles, Tanjung Priok, Hamburg, Dubai, and more.
- **Live Weather Integration (Open-Meteo):** Real-time sea-level weather conditions (temperature, wind speed, weather codes) fetched live from Open-Meteo without requiring API keys or incurring rate limits.
- **Multi-Currency Customs Valuation:** Dual-currency valuation displaying USD alongside local destination currency (EUR, IDR, SGD, JPY, CNY) powered by Frankfurter FX API.
- **Exception Management Workflow:** Disruption handling (`WEATHER`, `CUSTOMS_HOLD`, `PORT_CONGESTION`, `MECHANICAL`) with an explicit operational state transition (`OPEN` &rarr; `INVESTIGATING` &rarr; `RESOLVED`).
- **Comprehensive Notification Center:** In-app header bell and dedicated `/notifications` management with unread badge indicators and batch read actions.
- **Regulatory Audit Trail (`/audit-logs`):** Append-only compliance log capturing user actions, IP addresses, timestamps, and model mutations. Restricted strictly to Administrators (403 Forbidden for non-admins).
- **Maritime Glossary & Educational Hub:** Built-in dashboard guide explaining critical terminology (AIS, UN/LOCODE, HS Code, ETA vs Expected, Delay Severity, Berthing).

---

## 3. System Architecture & Tech Stack

```
   ┌─────────────────────────────────────────────────────────────┐
   │                    Client Presentation                      │
   │  Blade Templates + Tailwind CSS + Leaflet.js + Chart.js     │
   └──────────────────────────────┬──────────────────────────────┘
                                  │ HTTPS / REST JSON
   ┌──────────────────────────────▼──────────────────────────────┐
   │                 Security & Middleware Gateway               │
   │      CSRF Protection | Laravel Sanctum | Strict RBAC        │
   └──────────────────────────────┬──────────────────────────────┘
                                  │
   ┌──────────────────────────────▼──────────────────────────────┐
   │                    HTTP Controllers                         │
   │    Web Controllers (6)     │     REST API Controllers (4)   │
   └──────────────────────────────┬──────────────────────────────┘
                                  │
   ┌──────────────────────────────▼──────────────────────────────┐
   │                 Domain Services & Business Rules            │
   │  ShipmentService  │  EtaCalculationService  │  WeatherService │
   │  CurrencyService  │  TelemetryService       │  AuditLogService│
   └──────────────────────────────┬──────────────────────────────┘
                                  │ Eloquent ORM
   ┌──────────────────────────────▼──────────────────────────────┐
   │               Database Storage (MySQL / SQLite)             │
   │   9 Relational Tables | Indexed Foreign Keys | Audit Logs   │
   └─────────────────────────────────────────────────────────────┘
```

For complete Entity-Relationship Diagrams (ERD) and Data Dictionary tables, consult [ARCHITECTURE_AND_ERD.md](file:///d:/CargoTrace/docs/ARCHITECTURE_AND_ERD.md).

---

## 4. User Roles & Access Control (RBAC)

Every route, API endpoint, and Blade action strictly verifies server-side authorization:

| Role | Demo Credentials | Scope of Access |
|---|---|---|
| **Administrator** | `admin@cargotrace.com` / `password` | **Full Platform Authority:** Manages all 24 shipments ($1.62M valuation), master ports, carriers, vessels, system settings, and regulatory audit logs. |
| **Logistics Operator** | `operator@cargotrace.com` / `password` | **Operational Management:** Creates and updates shipments, advances status lifecycles, adds tracking events, and investigates/resolves exceptions. Audit logs blocked. |
| **Customer** | `customer@cargotrace.com` / `password` | **Isolated Multi-Tenant Access:** Strictly views their own 7 shipments (~$380k USD valuation), tracking timelines, and route maps. Creating shipments, internal exceptions, and audit logs blocked. |

> **Privacy & Identity Security:** Account switching enforces mandatory session termination (`Auth::logout()`, token regeneration) and requires password re-entry to prevent identity spoofing.

---

## 5. Installation & Setup Guide

### Prerequisites
- **PHP** >= 8.2 (with `pdo`, `mbstring`, `openssl`, `curl` extensions)
- **Composer** >= 2.x
- **Node.js** >= 18.x & **NPM**
- **MySQL / MariaDB** (or SQLite for development)

### Step-by-Step Installation

```bash
# 1. Clone repository
git clone https://github.com/your-username/cargotrace.git
cd cargotrace

# 2. Install PHP backend dependencies
composer install

# 3. Install frontend dependencies and build assets
npm install
npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Run database migrations and seed realistic demo data
php artisan migrate:fresh --seed

# 6. Launch local development server
php artisan serve --port=8000
```

Once running, access the application in your browser at `http://127.0.0.1:8000`.

---

## 6. Automated Testing & Verification

CargoTrace includes a comprehensive automated test suite of **134 Unit and Feature tests** covering happy paths, boundary conditions, state transition failures, and anti-IDOR authorization checks.

```bash
# Execute entire test suite
php artisan test
```

### Test Coverage Highlights:
- **`AuthApiTest` & `AuthWebTest`:** User registration, Sanctum token issue, session invalidation, and mandatory logout on role switch.
- **`ShipmentLifecycleTest`:** Strict state machine validation preventing illegal retroactive jumps (e.g., `DELIVERED` &rarr; `CREATED`).
- **`CustomerAccessIsolationTest`:** Validates that Customer B cannot inspect, update, or delete Customer A's shipments.
- **`EtaCalculationServiceTest`:** Deterministic delay calculation and severity threshold accuracy.
- **`NotificationAndAuditWebTest`:** Unread notifications badge logic and 403 Forbidden enforcement on `/audit-logs`.

---

## 7. REST API Documentation & Postman Collection

The REST API implements standard HTTP response codes, JSON schemas, and Bearer token authentication via Laravel Sanctum.

### Postman Collection
A complete Postman v2.1 collection file is provided in the root directory:  
👉 **[`CargoTrace.postman_collection.json`](file:///d:/CargoTrace/CargoTrace.postman_collection.json)**

Import this collection into Postman to immediately execute and verify all 22+ endpoints across:
1. `Authentication` (`/api/register`, `/api/login`, `/api/logout`, `/api/user`)
2. `Shipments` (`GET /api/shipments`, `POST /api/shipments`, `PATCH /api/shipments/{id}/status`, etc.)
3. `Tracking Events` (`GET /api/shipments/{id}/tracking`, `POST /api/shipments/{id}/tracking`)
4. `Exceptions` (`POST /api/shipments/{id}/exceptions`, `PATCH /api/exceptions/{id}/resolve`)
5. `Master Data` (`GET /api/locations`, `GET /api/carriers`, `GET /api/vessels`)
6. `Notifications & Audit` (`GET /api/notifications`, `GET /api/audit-logs`)

---

## 8. Scope Boundaries & Simulated Integrations (Academic Transparency)

In strict accordance with Slide 04 of the UAS Final Project Brief:
- **Physical AIS Satellites:** Direct satellite transponder hardware integration is outside academic project scope. Vessel AIS coordinates are simulated through realistic mathematical voyage vectors and accessible via the automated Python collector script (`python/ais_collector.py`) or the in-app map simulation button.
- **Live Weather Data:** Fully operational and live without mocks using Open-Meteo's open marine weather API.
- **FX Valuation Rates:** Live rates retrieved via Frankfurter API with local fallback mechanisms.

---

## 9. Known Limitations & Future Work

- **Offline Tile Caching:** Leaflet.js currently requires internet connectivity to stream OpenStreetMap tiles. Future iterations could bundle pre-rendered vector MBTiles for air-gapped terminal operations.
- **WebSocket Streaming:** Real-time map vessel movement currently refreshes via simulated polling/AJAX. Transitioning to Laravel Reverb / WebSockets would enable sub-second live vessel tracking.
- **Container IoT Sensor Ingestion:** Direct MQTT / CoAP protocol support for reefer container temperature and humidity telemetry.

---

## 10. Attributions & External Open-Source Libraries

- **Laravel Framework:** MIT License by Taylor Otwell & Laravel Community.
- **Tailwind CSS:** MIT License by Tailwind Labs.
- **Leaflet.js:** BSD-2-Clause License by Volodymyr Agafonkin.
- **Chart.js:** MIT License by Chart.js Contributors.
- **Open-Meteo:** Open marine weather data under CC-BY 4.0.
- **Frankfurter API:** Open-source exchange rate API.
- **Unsplash:** Maritime harbor photography under Unsplash License.
