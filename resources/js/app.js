import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "./bootstrap";

window.L = L;

document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements
    const loginPanel = document.getElementById("login-panel");
    const trackingPanel = document.getElementById("tracking-panel");
    const mapPanel = document.getElementById("map-panel");
    const shipmentDetailsCard = document.getElementById("shipment-details-card");
    const timelineContainer = document.getElementById("timeline-container");
    const timelineList = document.getElementById("timeline-list");

    const loginForm = document.getElementById("login-form");
    const shipmentSelect = document.getElementById("shipment-select");
    const message = document.getElementById("message");
    const logoutButton = document.getElementById("logout-button");
    const userBadge = document.getElementById("user-badge");

    // Guard: Only run legacy SPA auth/fetch if legacy login-form or shipment-select exists
    if (!loginForm && !shipmentSelect) {
        return;
    }

    let map = null;
    let routeLayer = null;
    let markerLayer = null;
    let token = sessionStorage.getItem("cargotrace_token");
    let shipmentsCache = [];

    // Helper: API Request wrapper with Bearer token
    async function apiRequest(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...options.headers,
            },
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || "Terjadi kesalahan pada server.");
        }

        return result;
    }

    // Helper: Show feedback message
    function showMessage(text, isError = false) {
        if (!message) return;
        message.textContent = text;
        message.className = isError
            ? "p-3 rounded-lg text-sm bg-rose-50 text-rose-700 border border-rose-200"
            : "p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200";
        message.classList.remove("hidden");
    }

    function clearMessage() {
        if (!message) return;
        message.textContent = "";
        message.classList.add("hidden");
    }

    // Navigation panel toggling
    function showTrackingPanel() {
        loginPanel.classList.add("hidden");
        trackingPanel.classList.remove("hidden");
        mapPanel.classList.remove("hidden");
        timelineContainer.classList.remove("hidden");
    }

    function showLoginPanel() {
        loginPanel.classList.remove("hidden");
        trackingPanel.classList.add("hidden");
        mapPanel.classList.add("hidden");
        timelineContainer.classList.add("hidden");
        if (shipmentDetailsCard) shipmentDetailsCard.classList.add("hidden");
    }

    // Marker Icon Creators using SVG DivIcons (robust, no missing asset path issues)
    function createCustomMarkerIcon(type) {
        let bgClass = "bg-blue-600 ring-4 ring-blue-200";
        let iconSvg = `<svg style="width:16px;height:16px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`;

        if (type === "ORIGIN") {
            bgClass = "bg-emerald-600 ring-4 ring-emerald-200";
            iconSvg = `<svg style="width:16px;height:16px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>`;
        } else if (type === "DESTINATION") {
            bgClass = "bg-purple-600 ring-4 ring-purple-200";
            iconSvg = `<svg style="width:16px;height:16px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>`;
        } else if (type === "EXCEPTION") {
            bgClass = "bg-rose-600 ring-4 ring-rose-200";
            iconSvg = `<svg style="width:16px;height:16px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
        }

        return L.divIcon({
            className: "custom-leaflet-marker",
            html: `
                <div style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9999px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);" class="${bgClass}">
                    ${iconSvg}
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16],
        });
    }

    // Popup HTML generator conforming to PPT Slide 09: "Popup berisi status, waktu, dan lokasi."
    function createPopupHtml(point) {
        const typeBadgeStyles = {
            ORIGIN: "background:#d1fae5; color:#065f46; border:1px solid #6ee7b7;",
            CHECKPOINT: "background:#dbeafe; color:#1e40af; border:1px solid #93c5fd;",
            EXCEPTION: "background:#ffe4e6; color:#9f1239; border:1px solid #fca5a5;",
            DESTINATION: "background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe;",
        };

        const badgeStyle = typeBadgeStyles[point.type] || "background:#f1f5f9; color:#334155;";

        let weatherSection = "";
        if (point.weather_condition || point.weather_temp_c !== null) {
            const temp = point.weather_temp_c !== null ? `${point.weather_temp_c}°C` : "";
            const cond = point.weather_condition || "Cuaca Tercatat";
            weatherSection = `
                <div style="margin-top:8px; padding-top:6px; border-top:1px dashed #cbd5e1; display:flex; align-items:center; gap:6px; font-size:12px; color:#0369a1; background:#f0f9ff; padding:5px 8px; border-radius:6px;">
                    <span>🌤️</span>
                    <span style="font-weight:600;">${cond}</span>
                    ${temp ? `<span style="font-weight:700; color:#0c4a6e;">(${temp})</span>` : ""}
                </div>
            `;
        }

        return `
            <div style="font-family: inherit; font-size: 13px; color: #1e293b; line-height: 1.4; min-width: 200px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span style="padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; ${badgeStyle}">
                        ${point.type}
                    </span>
                    <span style="font-size:11px; font-weight:600; color:#64748b;">
                        ${point.status || ""}
                    </span>
                </div>
                <div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:4px;">
                    📍 ${point.location_name || point.label}
                </div>
                <div style="font-size:12px; color:#475569; margin-bottom:4px;">
                    🕒 ${point.event_time ? point.event_time : "Waktu tidak dicatat"}
                </div>
                <div style="font-size:11px; color:#94a3b8; font-family: monospace;">
                    Koordinat: ${Number(point.lat).toFixed(4)}, ${Number(point.lng).toFixed(4)}
                </div>
                ${weatherSection}
            </div>
        `;
    }

    // Initialize Leaflet Map with OpenStreetMap and Tile Attribution
    function initializeMap() {
        if (map) {
            map.invalidateSize();
            return;
        }

        // Default center: Indonesia Maritime Archipelagic area
        map = L.map("map").setView([-2.5489, 118.0149], 5);

        // OpenStreetMap Tile Layer with standard OpenStreetMap attribution
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        routeLayer = L.layerGroup().addTo(map);
        markerLayer = L.layerGroup().addTo(map);
    }

    // Load list of shipments for the authenticated user
    async function loadShipments() {
        showMessage("Memuat daftar pengiriman...");

        try {
            const userProfile = await apiRequest("/api/user");
            if (userBadge && userProfile?.data) {
                userBadge.textContent = `${userProfile.data.name} (${userProfile.data.role})`;
                userBadge.classList.remove("hidden");
            }

            const result = await apiRequest("/api/shipments?per_page=50");
            const shipments = result.data?.data ?? result.data ?? [];
            shipmentsCache = shipments;

            shipmentSelect.innerHTML =
                '<option value="">-- Pilih nomor pengiriman untuk melihat peta --</option>';

            shipments.forEach((shipment) => {
                const option = document.createElement("option");
                option.value = shipment.id;
                option.textContent = `${shipment.shipment_number} | ${shipment.current_status} | ${shipment.carrier?.name || 'Carrier'}`;
                shipmentSelect.appendChild(option);
            });

            initializeMap();

            showMessage(
                shipments.length > 0
                    ? `${shipments.length} pengiriman ditemukan untuk akun Anda.`
                    : "Belum ada data pengiriman.",
            );

            // Auto-select first shipment if available
            if (shipments.length > 0) {
                shipmentSelect.value = shipments[0].id;
                await loadTracking(shipments[0].id);
            }
        } catch (error) {
            showMessage(error.message, true);
        }
    }

    // Render shipment overview details card
    function renderShipmentDetails(shipment, tracking) {
        if (!shipmentDetailsCard) return;

        const statusBadges = {
            CREATED: "bg-slate-100 text-slate-800 border-slate-300",
            BOOKED: "bg-blue-100 text-blue-800 border-blue-300",
            PICKED_UP: "bg-cyan-100 text-cyan-800 border-cyan-300",
            IN_TRANSIT: "bg-amber-100 text-amber-800 border-amber-300",
            OUT_FOR_DELIVERY: "bg-indigo-100 text-indigo-800 border-indigo-300",
            DELIVERED: "bg-emerald-100 text-emerald-800 border-emerald-300",
            EXCEPTION: "bg-rose-100 text-rose-800 border-rose-300",
            CANCELLED: "bg-gray-100 text-gray-800 border-gray-300",
        };

        const badgeClass = statusBadges[tracking.current_status] || "bg-slate-100 text-slate-800";

        document.getElementById("detail-shipment-number").textContent = tracking.shipment_number;
        const statusEl = document.getElementById("detail-status");
        statusEl.textContent = tracking.current_status;
        statusEl.className = `px-3 py-1 rounded-full text-xs font-bold border ${badgeClass}`;

        const vesselName = tracking.vessel?.name || shipment?.vessel?.name || "Belum Ditugaskan";
        const imoNum = (tracking.vessel?.imo_number || shipment?.vessel?.imo_number) ? ` (IMO: ${tracking.vessel?.imo_number || shipment?.vessel?.imo_number})` : "";
        const vesselEl = document.getElementById("detail-vessel");
        if (vesselEl) vesselEl.textContent = vesselName + imoNum;

        const containerEl = document.getElementById("detail-container");
        if (containerEl) containerEl.textContent = tracking.container_number || shipment?.container_number || "-";

        document.getElementById("detail-carrier").textContent = shipment?.carrier?.name || "-";
        document.getElementById("detail-origin").textContent = shipment?.origin?.name || "-";
        document.getElementById("detail-destination").textContent = shipment?.destination?.name || "-";
        document.getElementById("detail-expected-arrival").textContent = shipment?.expected_arrival || "-";

        shipmentDetailsCard.classList.remove("hidden");
    }

    // Render chronological tracking timeline
    function renderTimeline(events) {
        if (!timelineList) return;
        timelineList.innerHTML = "";

        if (!events || events.length === 0) {
            timelineList.innerHTML = '<p class="text-sm text-slate-500 py-4">Belum ada riwayat timeline tracking untuk pengiriman ini.</p>';
            return;
        }

        events.forEach((item, index) => {
            const isException = item.event_type === "EXCEPTION";
            const isLatest = index === events.length - 1;

            const dotColor = isException
                ? "bg-rose-500 ring-4 ring-rose-100"
                : isLatest
                ? "bg-emerald-500 ring-4 ring-emerald-100"
                : "bg-blue-500 ring-4 ring-blue-100";

            let weatherBadge = "";
            if (item.weather_condition || item.weather_temp_c !== null) {
                const temp = item.weather_temp_c !== null ? `${item.weather_temp_c}°C` : "";
                const cond = item.weather_condition || "";
                weatherBadge = `
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-sky-800 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md mt-1">
                        🌤️ ${cond} ${temp ? `<b>(${temp})</b>` : ""}
                    </span>
                `;
            }

            const li = document.createElement("li");
            li.className = "mb-6 ms-6 relative";
            li.innerHTML = `
                <span class="absolute -start-3 flex items-center justify-center w-6 h-6 rounded-full ${dotColor}"></span>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                        <span class="font-bold text-sm ${isException ? 'text-rose-700' : 'text-slate-900'}">
                            ${item.event_type}
                        </span>
                        <time class="text-xs text-slate-500 font-mono">
                            ${item.event_time || "-"}
                        </time>
                    </div>
                    <p class="text-xs text-slate-600 mb-1">
                        📍 <b>${item.location?.name || "Checkpoint"}</b>
                    </p>
                    <p class="text-xs text-slate-600">
                        ${item.description || "-"}
                    </p>
                    ${weatherBadge}
                </div>
            `;

            timelineList.appendChild(li);
        });
    }

    // Load Tracking Data & Render onto Leaflet Map
    async function loadTracking(shipmentId) {
        if (!shipmentId) return;

        showMessage("Memuat rute peta & riwayat pengiriman...");

        try {
            const result = await apiRequest(`/api/shipments/${shipmentId}/tracking`);
            const tracking = result.data;
            const shipment = shipmentsCache.find((s) => s.id == shipmentId);

            initializeMap();
            routeLayer.clearLayers();
            markerLayer.clearLayers();

            renderShipmentDetails(shipment, tracking);
            renderTimeline(tracking.timeline || []);

            const points = tracking.route_points || [];

            if (points.length === 0) {
                map.setView([-2.5489, 118.0149], 5);
                showMessage("Belum ada koordinat rute untuk pengiriman ini.");
                return;
            }

            const coordinates = [];

            // Plot markers with custom icons and detailed popups
            points.forEach((point) => {
                const lat = Number(point.lat);
                const lng = Number(point.lng);
                coordinates.push([lat, lng]);

                const customIcon = createCustomMarkerIcon(point.type);
                const marker = L.marker([lat, lng], { icon: customIcon });

                marker.bindPopup(createPopupHtml(point), {
                    maxWidth: 280,
                    className: "custom-leaflet-popup",
                });

                marker.addTo(markerLayer);
            });

            // Connect points with Polyline route
            if (coordinates.length > 1) {
                const polyline = L.polyline(coordinates, {
                    color: "#2563eb",
                    weight: 4,
                    opacity: 0.85,
                    dashArray: "8, 6",
                    lineJoin: "round",
                }).addTo(routeLayer);

                map.fitBounds(polyline.getBounds(), {
                    padding: [45, 45],
                });
            } else {
                map.setView(coordinates[0], 11);
            }

            showMessage(
                `Peta rute & riwayat pengiriman ${tracking.shipment_number} berhasil ditampilkan.`,
            );
        } catch (error) {
            showMessage(error.message, true);
        }
    }

    // Login Form Submission
    loginForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        showMessage("Memproses login...");

        try {
            const result = await apiRequest("/api/login", {
                method: "POST",
                body: JSON.stringify({ email, password }),
            });

            token = result.data.token;
            sessionStorage.setItem("cargotrace_token", token);

            loginForm.reset();
            showTrackingPanel();
            await loadShipments();
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    // Shipment Dropdown Change Event
    shipmentSelect.addEventListener("change", (event) => {
        loadTracking(event.target.value);
    });

    // Logout Button Event
    logoutButton.addEventListener("click", async () => {
        try {
            await apiRequest("/api/logout", { method: "POST" });
        } catch (error) {
            console.error("Logout API:", error.message);
        }

        token = null;
        sessionStorage.removeItem("cargotrace_token");
        shipmentsCache = [];

        shipmentSelect.innerHTML = '<option value="">Pilih nomor pengiriman</option>';

        if (routeLayer) routeLayer.clearLayers();
        if (markerLayer) markerLayer.clearLayers();

        clearMessage();
        showLoginPanel();
    });

    // Check existing session token on startup
    if (token) {
        showTrackingPanel();
        loadShipments();
    } else {
        showLoginPanel();
    }
});
