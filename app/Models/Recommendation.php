<?php

namespace App\Models;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'alert_id',
        'title',
        'rationale',
        'priority',
        'status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => RecommendationPriority::class,
            'status' => RecommendationStatus::class,
            'generated_at' => 'datetime',
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

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }
}
