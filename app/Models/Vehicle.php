<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'make',
        'model',
        'vehicle_type',
        'manufacture_year',
        'mileage',
        'status',
        'driver_id',
        'last_maintenance_date',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'last_maintenance_date' => 'date',
            'manufacture_year' => 'integer',
            'mileage' => 'integer',
        ];
    }

    public function displayName(): string
    {
        return "{$this->registration_number} · {$this->make} {$this->model}";
    }

    public function ageInYears(?int $asOfYear = null): int
    {
        return ($asOfYear ?? (int) now()->year) - $this->manufacture_year;
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function telematicsRecords(): HasMany
    {
        return $this->hasMany(TelematicsRecord::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function maintenanceRisks(): HasMany
    {
        return $this->hasMany(MaintenanceRisk::class);
    }

    public function latestRisk(): HasOne
    {
        return $this->hasOne(MaintenanceRisk::class)->latestOfMany('calculated_at');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }
}
