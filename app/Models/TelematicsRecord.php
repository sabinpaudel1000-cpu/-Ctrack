<?php

namespace App\Models;

use Database\Factories\TelematicsRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelematicsRecord extends Model
{
    /** @use HasFactory<TelematicsRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'recorded_at',
        'latitude',
        'longitude',
        'speed',
        'distance_km',
        'fuel_consumed_l',
        'engine_temperature',
        'harsh_acceleration',
        'harsh_braking',
        'speeding',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'speed' => 'integer',
            'distance_km' => 'float',
            'fuel_consumed_l' => 'float',
            'engine_temperature' => 'integer',
            'harsh_acceleration' => 'boolean',
            'harsh_braking' => 'boolean',
            'speeding' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
