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
     * Check if shipment is marked as delayed.
     */
    public function isDelayed(): bool
    {
        return (bool) $this->is_delayed;
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
        return $query->where('is_delayed', true);
    }

    public function scopeInTransit($query)
    {
        return $query->where('current_status', 'IN_TRANSIT');
    }

    public function scopeDelivered($query)
    {
        return $query->where('current_status', 'DELIVERED');
    }
}
