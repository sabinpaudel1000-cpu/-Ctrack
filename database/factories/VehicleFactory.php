<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'registration_number' => 'NSW-'.fake()->unique()->numerify('###'),
            'make' => fake()->randomElement(['Toyota', 'Ford', 'Mitsubishi', 'Isuzu']),
            'model' => fake()->randomElement(['HiAce', 'Ranger', 'Triton', 'NPR']),
            'vehicle_type' => fake()->randomElement(VehicleType::cases()),
            'manufacture_year' => fake()->numberBetween(2014, 2024),
            'mileage' => fake()->numberBetween(20000, 280000),
            'status' => VehicleStatus::Active,
            'driver_id' => null,
            'last_maintenance_date' => now()->subDays(fake()->numberBetween(20, 400))->toDateString(),
        ];
    }
}
