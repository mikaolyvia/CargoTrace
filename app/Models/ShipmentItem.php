<?php

namespace App\Models;

use Database\Factories\ShipmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    /** @use HasFactory<ShipmentItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'item_name',
        'hs_code',
        'quantity',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight_kg' => 'float',
            'length_cm' => 'float',
            'width_cm' => 'float',
            'height_cm' => 'float',
        ];
    }

    /**
     * Parent shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Calculate subtotal weight for this line item (kg).
     */
    public function getSubtotalWeightAttribute(): float
    {
        return round($this->quantity * (float) $this->weight_kg, 2);
    }

    /**
     * Calculate subtotal volume in cubic meters (cbm).
     * Returns 0.0 if any dimension is null or non-positive.
     */
    public function getSubtotalVolumeCbmAttribute(): float
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm &&
            $this->length_cm > 0 && $this->width_cm > 0 && $this->height_cm > 0) {
            return round(($this->quantity * ($this->length_cm * $this->width_cm * $this->height_cm)) / 1000000, 4);
        }

        return 0.0;
    }

    /**
     * Bootstrap model events to auto-recalculate shipment totals.
     */
    protected static function booted(): void
    {
        static::saved(function (ShipmentItem $item) {
            if ($item->shipment) {
                $item->shipment->recalculateTotals();
            }
        });

        static::deleted(function (ShipmentItem $item) {
            if ($item->shipment) {
                $item->shipment->recalculateTotals();
            }
        });
    }
}
