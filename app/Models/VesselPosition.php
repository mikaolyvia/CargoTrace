<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'latitude',
        'longitude',
        'speed_knots',
        'heading_degrees',
        'status',
        'source',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed_knots' => 'float',
        'heading_degrees' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
