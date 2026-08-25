<?php

namespace App\Http\Requests;

use App\Enums\DriverStatus;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends StoreDriverRequest
{
    public function rules(): array
    {
        $driver = $this->route('driver');
        $driverId = $driver->id;

        return [
            'employee_code' => ['required', 'string', 'max:32', Rule::unique('drivers', 'employee_code')->ignore($driverId)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('drivers', 'email')->ignore($driverId)],
            'phone' => ['nullable', 'string', 'max:32'],
            'licence_number' => ['required', 'string', 'max:64', Rule::unique('drivers', 'licence_number')->ignore($driverId)],
            'licence_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(DriverStatus::class)],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'vehicle_id' => [
                'nullable',
                'exists:vehicles,id',
                $this->availableVehicleRule($driverId),
                $this->activeDriverVehicleRule(),
            ],
        ];
    }
}
