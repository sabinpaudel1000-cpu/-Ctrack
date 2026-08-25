<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_analytics(): void
    {
        $this->get(route('analytics.index'))->assertRedirect(route('login'));
    }

    public function test_empty_analytics_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('30-day rollups')
            ->assertSee('No vehicles to analyse yet.')
            ->assertSee('No drivers to analyse yet.')
            ->assertSee('No telematics in this period.');
    }

    public function test_analytics_totals_are_calculated_from_telematics(): void
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['first_name' => 'Priya', 'last_name' => 'Sharma']);
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'NSW-AN1',
            'driver_id' => $driver->id,
        ]);

        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'distance_km' => 50,
            'fuel_consumed_l' => 6,
            'speed' => 60,
            'harsh_braking' => true,
            'harsh_acceleration' => false,
            'speeding' => false,
            'recorded_at' => now()->subDay(),
        ]);
        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'distance_km' => 50,
            'fuel_consumed_l' => 6,
            'speed' => 80,
            'harsh_braking' => true,
            'harsh_acceleration' => false,
            'speeding' => true,
            'recorded_at' => now()->subDays(2),
        ]);

        // Outside the default 30-day window — must not be included.
        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'distance_km' => 999,
            'fuel_consumed_l' => 99,
            'recorded_at' => now()->subDays(40),
        ]);

        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('100 km')
            ->assertSee('12 L')
            ->assertSee('NSW-AN1')
            ->assertSee('Priya Sharma')
            ->assertDontSee('999');

        // 2 harsh brakes * 1.5 + 1 speeding * 2.5 = 5.5 → score 95
        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertSee('95');
    }

    public function test_invalid_period_falls_back_to_thirty_days(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('analytics.index', ['days' => 15]))
            ->assertOk()
            ->assertSee('30-day rollups');
    }

    public function test_seven_day_period_excludes_older_telematics(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-AN7']);

        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'distance_km' => 25,
            'fuel_consumed_l' => 2,
            'recorded_at' => now()->subDays(3),
        ]);
        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'distance_km' => 400,
            'fuel_consumed_l' => 40,
            'recorded_at' => now()->subDays(20),
        ]);

        $this->actingAs($user)
            ->get(route('analytics.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('7-day rollups')
            ->assertSee('25 km')
            ->assertDontSee('400 km');
    }
}
