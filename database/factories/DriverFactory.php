<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Driver> */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_code' => 'DRV-'.fake()->unique()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '04'.fake()->numerify('########'),
            'licence_number' => strtoupper(fake()->bothify('??######')),
            'licence_expiry' => now()->addYears(3)->toDateString(),
            'status' => DriverStatus::Active,
            'hire_date' => fake()->dateTimeBetween('-6 years', '-6 months')->format('Y-m-d'),
        ];
    }
}
