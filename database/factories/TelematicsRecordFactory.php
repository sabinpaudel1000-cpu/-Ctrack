<?php

namespace Database\Factories;

use App\Models\TelematicsRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TelematicsRecord> */
class TelematicsRecordFactory extends Factory
{
    public function definition(): array
    {
        $distance = fake()->randomFloat(2, 4, 28);

        return [
            'recorded_at' => now()->subHours(fake()->numberBetween(1, 200)),
            'latitude' => fake()->randomFloat(7, -33.95, -33.75),
            'longitude' => fake()->randomFloat(7, 150.95, 151.30),
            'speed' => fake()->numberBetween(20, 95),
            'distance_km' => $distance,
            'fuel_consumed_l' => round($distance * fake()->randomFloat(2, 0.08, 0.16), 2),
            'engine_temperature' => fake()->numberBetween(85, 108),
            'harsh_acceleration' => false,
            'harsh_braking' => false,
            'speeding' => false,
        ];
    }
}
