<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Retrieve aggregated operational metrics tailored by user role.
     *
     * @param User|null $user
     * @return array
     */
    public function getMetrics(?User $user = null): array
    {
        $isCustomer = $user && $user->isCustomer();
        $customerId = $isCustomer ? $user->id : null;

        // Base shipment query scoped by customer ownership if customer role
        $shipmentsQuery = Shipment::query();
        if ($customerId) {
            $shipmentsQuery->where('customer_id', $customerId);
        }

        $totalShipments = (clone $shipmentsQuery)->count();
        $inTransitCount = (clone $shipmentsQuery)->where('current_status', 'IN_TRANSIT')->count();
        $deliveredCount = (clone $shipmentsQuery)->where('current_status', 'DELIVERED')->count();
        $totalCargoValuationUsd = (float) ((clone $shipmentsQuery)->sum('total_declared_value') ?? 0.0);
        $totalCargoValuationIdr = $this->currencyService->convert($totalCargoValuationUsd, 'USD', 'IDR');
        $totalCargoValuationEur = $this->currencyService->convert($totalCargoValuationUsd, 'USD', 'EUR');

        // Delayed shipments query
        $delayedCount = (clone $shipmentsQuery)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('current_status', '!=', 'DELIVERED')
                        ->whereColumn('estimated_arrival', '>', 'expected_arrival');
                })->orWhere(function ($sub) {
                    $sub->where('current_status', 'DELIVERED')
                        ->whereNotNull('actual_arrival')
                        ->whereColumn('actual_arrival', '>', 'expected_arrival');
                });
            })->count();

        // Open exceptions query
        $exceptionsQuery = ShipmentException::whereIn('status', [
            ShipmentException::STATUS_OPEN,
            ShipmentException::STATUS_INVESTIGATING,
        ]);

        if ($customerId) {
            $exceptionsQuery->whereHas('shipment', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            });
        }
        $openExceptionsCount = $exceptionsQuery->count();

        // Calculated rates
        $delayRate = $totalShipments > 0
            ? round(($delayedCount / $totalShipments) * 100, 1)
            : 0.0;

        $exceptionRate = $totalShipments > 0
            ? round(($openExceptionsCount / $totalShipments) * 100, 1)
            : 0.0;

        // 1. Status Distribution (for Doughnut/Bar Chart)
        $statuses = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'EXCEPTION', 'CANCELLED'];
        $statusCounts = (clone $shipmentsQuery)
            ->select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        $statusDistribution = [];
        foreach ($statuses as $st) {
            $statusDistribution[$st] = (int) ($statusCounts[$st] ?? 0);
        }

        // 2. Carrier Distribution (for Polar/Bar Chart)
        $carrierCounts = (clone $shipmentsQuery)
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select('carriers.name as carrier_name', DB::raw('count(shipments.id) as count'))
            ->groupBy('carriers.id', 'carriers.name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'carrier_name')
            ->toArray();

        // 3. Recent Shipments
        $recentShipments = (clone $shipmentsQuery)
            ->with(['carrier:id,name,code', 'origin:id,name,city,country,currency_code,currency_symbol', 'destination:id,name,city,country,currency_code,currency_symbol', 'customer:id,name', 'vessel:id,name,imo_number,status', 'items'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // 4. Active Maritime Vessels Fleet
        $activeVessels = \App\Models\Vessel::with('carrier:id,name,code')
            ->orderBy('name')
            ->limit(6)
            ->get();

        // 4. Role-specific recent items
        $recentAuditLogs = [];
        $recentNotifications = [];

        if (! $isCustomer) {
            $recentAuditLogs = AuditLog::with('user:id,name,role')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();
        }

        if ($user) {
            $recentNotifications = Notification::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();
        }

        return [
            'summary' => [
                'total_shipments' => $totalShipments,
                'in_transit' => $inTransitCount,
                'delivered' => $deliveredCount,
                'delayed' => $delayedCount,
                'open_exceptions' => $openExceptionsCount,
                'delay_rate_percentage' => $delayRate,
                'exception_rate_percentage' => $exceptionRate,
                'total_cargo_valuation_usd' => $totalCargoValuationUsd,
                'total_cargo_valuation_idr' => $totalCargoValuationIdr,
                'total_cargo_valuation_eur' => $totalCargoValuationEur,
                'formatted_valuation_usd' => '$ ' . number_format($totalCargoValuationUsd, 2),
                'formatted_valuation_idr' => 'Rp ' . number_format($totalCargoValuationIdr, 0, ',', '.'),
                'formatted_valuation_eur' => '€ ' . number_format($totalCargoValuationEur, 2, ',', '.'),
            ],
            'charts' => [
                'status_distribution' => $statusDistribution,
                'carrier_distribution' => $carrierCounts,
            ],
            'recent_shipments' => $recentShipments,
            'active_vessels' => $activeVessels,
            'recent_audit_logs' => $recentAuditLogs,
            'recent_notifications' => $recentNotifications,
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'role' => $user?->role,
                'is_customer' => $isCustomer,
            ],
        ];
    }
}
