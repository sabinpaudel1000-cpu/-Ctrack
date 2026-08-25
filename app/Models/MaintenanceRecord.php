<?php

namespace App\Models;

use App\Enums\MaintenanceServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'vehicle_id',
        'service_date',
        'service_type',
        'description',
        'odometer',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'service_type' => MaintenanceServiceType::class,
            'odometer' => 'integer',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
