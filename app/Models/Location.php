<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'city',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Scope a query to filter locations by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }

    /**
     * Helper to return Leaflet.js compatible coordinate array [lat, lng].
     *
     * @return array{0: float, 1: float}
     */
    public function toLatLng(): array
    {
        return [(float) $this->latitude, (float) $this->longitude];
    }

    /**
     * Shipments that originate from this location.
     */
    public function originShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_id');
    }

    /**
     * Shipments destined to this location.
     */
    public function destinationShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_id');
    }

    /**
     * Tracking events recorded at this location.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'location_id');
    }
}
