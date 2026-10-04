<?php

namespace App\Http\Requests;

use App\Enums\DriverStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks a new driver before it is saved.
 * Employee code and licence number are trimmed and stored in uppercase.
 * vehicle_id is checked here; the controller writes it on the vehicle row.
 */
class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedDriverInput());
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:32', 'unique:drivers,employee_code'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', 'unique:drivers,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'licence_number' => ['required', 'string', 'max:64', 'unique:drivers,licence_number'],
            'licence_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(DriverStatus::class)],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id', $this->availableVehicleRule(), $this->activeDriverVehicleRule()],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'employee code',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'licence_number' => 'licence number',
            'licence_expiry' => 'licence expiry',
            'hire_date' => 'hire date',
            'vehicle_id' => 'assigned vehicle',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.unique' => 'That employee code is already in use.',
            'email.unique' => 'That email is already assigned to another driver.',
            'licence_number.unique' => 'That licence number is already in use.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizedDriverInput(): array
    {
        $nullable = fn (string $key) => $this->filled($key) ? $this->input($key) : null;
        $code = $this->input('employee_code');
        $licence = $this->input('licence_number');

        return [
            'employee_code' => is_string($code) ? strtoupper(trim($code)) : $code,
            'first_name' => is_string($this->input('first_name')) ? trim($this->input('first_name')) : $this->input('first_name'),
            'last_name' => is_string($this->input('last_name')) ? trim($this->input('last_name')) : $this->input('last_name'),
            'licence_number' => is_string($licence) ? strtoupper(trim($licence)) : $licence,
            'email' => $nullable('email'),
            'phone' => $nullable('phone'),
            'licence_expiry' => $nullable('licence_expiry'),
            'hire_date' => $nullable('hire_date'),
            'vehicle_id' => $nullable('vehicle_id'),
        ];
    }

    protected function availableVehicleRule(?int $currentDriverId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($currentDriverId): void {
            if ($value === null || $value === '') {
                return;
            }

            $vehicle = Vehicle::query()->find($value);

            if ($vehicle && $vehicle->driver_id && (int) $vehicle->driver_id !== $currentDriverId) {
                $fail('That vehicle is already assigned to another driver.');
            }
        };
    }

    protected function activeDriverVehicleRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if ($this->input('status') !== DriverStatus::Active->value) {
                $fail('Only an active driver can be assigned a vehicle.');
            }
        };
    }
}
