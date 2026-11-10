#!/usr/bin/env python3
"""
CargoTrace - Automated AIS Maritime Data Collector
Part of UAS Web Programming 2 - CargoTrace Global Maritime Visibility Platform

This standalone Python module collects, validates, and synchronizes real-time
AIS vessel position telemetry with the Laravel CargoTrace core API.
"""

import sys
import json
import time
import argparse
import urllib.request
import urllib.error
from datetime import datetime, timezone

DEFAULT_API_URL = "http://127.0.0.1:8000"
DEFAULT_EMAIL = "operator@cargotrace.com"
DEFAULT_PASSWORD = "password"

# Realistic international shipping lanes coordinates simulation
SAMPLE_VESSELS_TELEMETRY = [
    {
        "imo_number": "9778791",
        "name": "Madrid Maersk",
        "latitude": 6.1200000,
        "longitude": 81.2000000,
        "speed_knots": 18.8,
        "heading_degrees": 280.0,
        "status": "UNDERWAY",
        "source": "PYTHON_AIS_COLLECTOR",
    },
    {
        "imo_number": "9811000",
        "name": "Ever Given",
        "latitude": 12.8500000,
        "longitude": 43.7500000,
        "speed_knots": 16.9,
        "heading_degrees": 320.0,
        "status": "UNDERWAY",
        "source": "PYTHON_AIS_COLLECTOR",
    },
    {
        "imo_number": "9839438",
        "name": "MSC Gülsün",
        "latitude": 1.1800000,
        "longitude": 104.2500000,
        "speed_knots": 15.2,
        "heading_degrees": 65.0,
        "status": "UNDERWAY",
        "source": "PYTHON_AIS_COLLECTOR",
    },
]


class CargoTraceAisCollector:
    def __init__(self, base_url=DEFAULT_API_URL, email=DEFAULT_EMAIL, password=DEFAULT_PASSWORD):
        self.base_url = base_url.rstrip("/")
        self.email = email
        self.password = password
        self.token = None

    def log(self, message):
        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        print(f"[{now}] [AIS-Collector] {message}")

    def login(self):
        self.log(f"Authenticating with CargoTrace API as {self.email}...")
        url = f"{self.base_url}/api/login"
        payload = json.dumps({
            "email": self.email,
            "password": self.password,
        }).encode("utf-8")

        req = urllib.request.Request(
            url,
            data=payload,
            headers={
                "Content-Type": "application/json",
                "Accept": "application/json",
            },
            method="POST"
        )

        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                data = json.loads(resp.read().decode("utf-8"))
                self.token = data.get("data", {}).get("token")
                if not self.token:
                    raise ValueError("No token in response data.")
                self.log(f"Login successful. Sanctum Bearer Token acquired.")
                return True
        except urllib.error.HTTPError as e:
            self.log(f"Login failed: HTTP {e.code} - {e.read().decode('utf-8')}")
            return False
        except Exception as e:
            self.log(f"Connection error: {e}")
            return False

    def push_vessel_position(self, vessel_data):
        if not self.token:
            if not self.login():
                return False

        url = f"{self.base_url}/api/integrations/vessel-positions"
        payload_dict = {
            "imo_number": vessel_data["imo_number"],
            "latitude": vessel_data["latitude"],
            "longitude": vessel_data["longitude"],
            "speed_knots": vessel_data.get("speed_knots", 15.0),
            "heading_degrees": vessel_data.get("heading_degrees", 0.0),
            "status": vessel_data.get("status", "UNDERWAY"),
            "source": vessel_data.get("source", "PYTHON_AIS_COLLECTOR"),
            "recorded_at": datetime.now(timezone.utc).isoformat(),
        }

        payload = json.dumps(payload_dict).encode("utf-8")

        req = urllib.request.Request(
            url,
            data=payload,
            headers={
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.token}",
            },
            method="POST"
        )

        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                result = json.loads(resp.read().decode("utf-8"))
                vessel_name = vessel_data.get("name", vessel_data["imo_number"])
                lat, lng = vessel_data["latitude"], vessel_data["longitude"]
                propagated = result.get("data", {}).get("propagated_shipments", 0)
                self.log(f"✓ Position pushed for '{vessel_name}' ({lat:.4f}, {lng:.4f}) | Propagated to {propagated} shipments.")
                return True
        except urllib.error.HTTPError as e:
            err_body = e.read().decode("utf-8")
            self.log(f"✗ Failed pushing position for IMO {vessel_data['imo_number']}: HTTP {e.code} - {err_body}")
            return False
        except Exception as e:
            self.log(f"✗ Exception sending position: {e}")
            return False

    def run_sync(self):
        self.log("Starting automated maritime AIS data synchronization cycle...")
        if not self.login():
            self.log("Cannot proceed without authentication. Exiting cycle.")
            return

        success_count = 0
        for vessel in SAMPLE_VESSELS_TELEMETRY:
            if self.push_vessel_position(vessel):
                success_count += 1

        self.log(f"Sync complete. {success_count}/{len(SAMPLE_VESSELS_TELEMETRY)} vessel positions synchronized.")


def main():
    parser = argparse.ArgumentParser(description="CargoTrace Automated AIS Maritime Data Collector")
    parser.add_argument("--url", default=DEFAULT_API_URL, help="Base URL of CargoTrace Laravel app")
    parser.add_argument("--email", default=DEFAULT_EMAIL, help="Operator/Admin email")
    parser.add_argument("--password", default=DEFAULT_PASSWORD, help="Operator/Admin password")
    parser.add_argument("--once", action="store_true", help="Run once and exit immediately")
    parser.add_argument("--interval", type=int, default=60, help="Loop interval in seconds (default: 60)")

    args = parser.parse_args()

    collector = CargoTraceAisCollector(base_url=args.url, email=args.email, password=args.password)

    if args.once:
        collector.run_sync()
    else:
        collector.log(f"Running automated collector in loop mode (interval: {args.interval}s). Press Ctrl+C to stop.")
        try:
            while True:
                collector.run_sync()
                time.sleep(args.interval)
        except KeyboardInterrupt:
            collector.log("Collector stopped by user.")


if __name__ == "__main__":
    main()
