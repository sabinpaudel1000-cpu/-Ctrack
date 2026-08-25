<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:32', 'unique:vehicles,registration_number'],
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'manufacture_year' => ['required', 'integer', 'min:1990', 'max:'.((int) now()->year + 1)],
            'mileage' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driver_id' => ['nullable', 'exists:drivers,id', 'unique:vehicles,driver_id'],
            'last_maintenance_date' => ['nullable', 'date'],
        ];
    }
}
