<?php

namespace App\Models;

use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_number',
        'container_number',
        'customer_id',
        'created_by',
        'carrier_id',
        'vessel_id',
        'origin_id',
        'destination_id',
        'current_location_id',
        'cargo_type',
        'total_weight_kg',
        'total_volume_cbm',
        'total_declared_value',
        'currency',
        'current_status',
        'expected_departure',
        'expected_arrival',
        'estimated_arrival',
        'actual_arrival',
        'is_delayed',
        'delay_hours',
        'delay_severity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_departure' => 'datetime',
            'expected_arrival' => 'datetime',
            'estimated_arrival' => 'datetime',
            'actual_arrival' => 'datetime',
            'is_delayed' => 'boolean',
            'delay_hours' => 'float',
            'total_weight_kg' => 'float',
            'total_volume_cbm' => 'float',
            'total_declared_value' => 'float',
        ];
    }

    /**
     * Customer who owns this shipment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Operator or Admin who registered this shipment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Logistics carrier transporting this shipment.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    /**
     * Assigned ocean cargo vessel.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id');
    }

    /**
     * Origin location (departure port/hub).
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    /**
     * Final destination location (delivery port/hub).
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    /**
     * Last reported physical location checkpoint.
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Itemized cargo manifest records.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id');
    }

    /**
     * Historical tracking events.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'shipment_id');
    }

    /**
     * Logged exceptions / operational incidents.
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class, 'shipment_id');
    }

    /**
     * In-app notifications tied to this shipment.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'shipment_id');
    }

    /**
     * Recalculate total weight (kg) and volume (cbm) from attached items.
     */
    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $totalWeight = 0.0;
        $totalVolume = 0.0;
        $totalValue = 0.0;

        foreach ($this->items as $item) {
            $totalWeight += $item->quantity * (float) $item->weight_kg;

            $itemTotalPrice = $item->total_price > 0 ? (float) $item->total_price : ($item->quantity * (float) ($item->unit_price ?? 0.0));
            if ($item->total_price <= 0 && ($item->unit_price ?? 0) > 0) {
                $item->update(['total_price' => $itemTotalPrice]);
            }
            $totalValue += $itemTotalPrice;

            if ($item->length_cm && $item->width_cm && $item->height_cm &&
                $item->length_cm > 0 && $item->width_cm > 0 && $item->height_cm > 0) {
                $itemCbm = ($item->length_cm * $item->width_cm * $item->height_cm) / 1000000;
                $totalVolume += $item->quantity * $itemCbm;
            }
        }

        $this->update([
            'total_weight_kg' => round($totalWeight, 2),
            'total_volume_cbm' => round($totalVolume, 4),
            'total_declared_value' => round($totalValue, 2),
        ]);
    }

    /**
     * Check if shipment is marked as delayed or past expected arrival.
     */
    public function isDelayed(): bool
    {
        if ($this->is_delayed) {
            return true;
        }

        if ($this->current_status !== 'DELIVERED' && $this->expected_arrival && now()->gt($this->expected_arrival)) {
            return true;
        }

        if ($this->estimated_arrival && $this->expected_arrival && $this->estimated_arrival->gt($this->expected_arrival)) {
            return true;
        }

        return false;
    }

    /**
     * Recalculate and synchronize delay status using EtaService.
     */
    public function syncDelayStatus(): self
    {
        return app(\App\Services\EtaService::class)->recalculateDelay($this);
    }

    /**
     * Convenient alias accessor for shipment_number.
     */
    public function getTrackingNumberAttribute(): string
    {
        return $this->shipment_number;
    }

    /**
     * Scopes
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeDelayed($query)
    {
        return $query->where(function ($q) {
            $q->where('is_delayed', true)
              ->orWhere(function ($sub) {
                  $sub->where('current_status', '!=', 'DELIVERED')
                      ->where(function ($nested) {
                          $nested->whereColumn('estimated_arrival', '>', 'expected_arrival')
                                 ->orWhere('expected_arrival', '<', now());
                      });
              })
              ->orWhere(function ($sub) {
                  $sub->where('current_status', 'DELIVERED')
                      ->whereNotNull('actual_arrival')
                      ->whereColumn('actual_arrival', '>', 'expected_arrival');
              });
        });
    }

    public function scopeInTransit($query)
    {
        return $query->where('current_status', 'IN_TRANSIT');
    }

    public function scopeDelivered($query)
    {
        return $query->where('current_status', 'DELIVERED');
    }

    /**
     * Scope query with flexible search and filtering criteria.
     */
    public function scopeFilter($query, array $filters = [])
    {
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('container_number', 'like', "%{$search}%")
                  ->orWhere('cargo_type', 'like', "%{$search}%")
                  ->orWhereHas('carrier', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vessel', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('imo_number', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        if (! empty($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }

        if (! empty($filters['origin_id'])) {
            $query->where('origin_id', $filters['origin_id']);
        }

        if (! empty($filters['destination_id'])) {
            $query->where('destination_id', $filters['destination_id']);
        }

        if (! empty($filters['delay_status'])) {
            if ($filters['delay_status'] === 'delayed') {
                $query->delayed();
            } elseif ($filters['delay_status'] === 'on_schedule') {
                $query->where('is_delayed', false)
                      ->where(function ($q) {
                          $q->whereNull('expected_arrival')
                            ->orWhere('expected_arrival', '>=', now());
                      });
            }
        }

        if (! empty($filters['country'])) {
            $country = $filters['country'];
            $query->where(function ($q) use ($country) {
                $q->whereHas('origin', fn ($sub) => $sub->where('country', $country))
                  ->orWhereHas('destination', fn ($sub) => $sub->where('country', $country));
            });
        }

        return $query;
    }
}
