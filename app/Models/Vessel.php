<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'carrier_id',
        'name',
        'imo_number',
        'mmsi',
        'flag_country',
        'vessel_type',
        'capacity_teu',
        'status',
        'last_latitude',
        'last_longitude',
        'last_speed_knots',
        'last_heading_degrees',
        'last_position_at',
    ];

    protected $casts = [
        'capacity_teu' => 'integer',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_speed_knots' => 'float',
        'last_heading_degrees' => 'float',
        'last_position_at' => 'datetime',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VesselPosition::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'MAINTENANCE');
    }

    public function scopeByImo(Builder $query, string $imo): Builder
    {
        return $query->where('imo_number', $imo);
    }

    public function getCurrentLatitudeAttribute(): ?float
    {
        return $this->last_latitude;
    }

    public function getCurrentLongitudeAttribute(): ?float
    {
        return $this->last_longitude;
    }

    public function getCurrentSpeedKnotsAttribute(): ?float
    {
        return $this->last_speed_knots;
    }

    public function getCurrentHeadingDegreesAttribute(): ?float
    {
        return $this->last_heading_degrees;
    }
}
