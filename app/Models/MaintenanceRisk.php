<?php

namespace App\Models;

use App\Enums\RiskLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRisk extends Model
{
    protected $fillable = [
        'vehicle_id',
        'score',
        'level',
        'factors_json',
        'algorithm_version',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'level' => RiskLevel::class,
            'factors_json' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
