<?php

namespace App\Models;

use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'location_id',
        'event_type',
        'event_time',
        'latitude',
        'longitude',
        'description',
        'weather_condition',
        'weather_temp_c',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'weather_temp_c' => 'float',
        ];
    }

    /**
     * Enforce strict append-only immutability.
     * Historical tracking events cannot be modified or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Tracking events are immutable (append-only) and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Tracking events are immutable (append-only) and cannot be deleted.');
        });
    }

    /**
     * The shipment associated with this event.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * The physical location checkpoint for this event.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * The operator or system user who recorded this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this event has valid GPS/map coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Return Leaflet.js compatible coordinate array [lat, lng].
     *
     * @return array{0: float, 1: float}|null
     */
    public function toLatLng(): ?array
    {
        if ($this->hasCoordinates()) {
            return [(float) $this->latitude, (float) $this->longitude];
        }

        return null;
    }
}
