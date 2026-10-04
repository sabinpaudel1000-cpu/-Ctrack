<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_predictions(): void
    {
        $this->get(route('predictions.index'))->assertRedirect(route('login'));
    }

    public function test_predictions_page_loads_for_a_signed_in_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('predictions.index'))
            ->assertOk()
            ->assertSee('Predictions')
            ->assertSee('statistical trend model')
            ->assertSee('Flagged vehicles')
            ->assertSee('Drivers at risk')
            ->assertSee('No vehicles are more than 15% above the fleet average.')
            ->assertSee('No drivers are at medium or high risk.');
    }

    public function test_short_history_does_not_crash_and_says_not_enough_data(): void
    {
        $user = User::factory()->create();
        $thin = Vehicle::factory()->create(['registration_number' => 'NSW-THIN']);
        Vehicle::factory()->create(['registration_number' => 'NSW-NONE']);
        Driver::factory()->create(['first_name' => 'No', 'last_name' => 'History']);

        foreach ([1, 2, 3] as $daysAgo) {
            TelematicsRecord::factory()->create([
                'vehicle_id' => $thin->id,
                'driver_id' => null,
                'distance_km' => 20,
                'fuel_consumed_l' => 4,
                'recorded_at' => now()->subDays($daysAgo),
            ]);
        }

        $this->actingAs($user)
            ->get(route('predictions.index'))
            ->assertOk()
            ->assertSee('NSW-THIN: not enough data')
            ->assertSee('NSW-NONE: not enough data')
            ->assertSee('No History: not enough data');
    }

    public function test_recalculate_twice_does_not_duplicate_open_prediction_alerts(): void
    {
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'NSW-UP',
            'driver_id' => $driver->id,
        ]);

        // 15 rising days. The forecast sits well above this vehicle's own average,
        // and 10 harsh brakes in the latest half make the driver medium risk.
        for ($day = 0; $day < 15; $day++) {
            TelematicsRecord::factory()->create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'distance_km' => 100,
                'fuel_consumed_l' => 10 + (2 * $day),
                'harsh_braking' => $day < 10,
                'harsh_acceleration' => false,
                'speeding' => false,
                'recorded_at' => now()->subDays(14 - $day),
            ]);
        }

        $this->artisan('fleet:recalculate')->assertSuccessful();

        $fuelId = Alert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('type', AlertType::PredictedHighFuel)
            ->where('status', AlertStatus::Open)
            ->value('id');
        $driverAlertId = Alert::query()
            ->where('driver_id', $driver->id)
            ->where('type', AlertType::PredictedDriverSafety)
            ->where('status', AlertStatus::Open)
            ->value('id');

        $this->assertNotNull($fuelId);
        $this->assertNotNull($driverAlertId);

        $this->artisan('fleet:recalculate')->assertSuccessful();

        $this->assertSame(1, Alert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('type', AlertType::PredictedHighFuel)
            ->where('status', AlertStatus::Open)
            ->count());
        $this->assertSame(1, Alert::query()
            ->where('driver_id', $driver->id)
            ->where('type', AlertType::PredictedDriverSafety)
            ->where('status', AlertStatus::Open)
            ->count());
        $this->assertSame($fuelId, Alert::query()->where('type', AlertType::PredictedHighFuel)->where('status', AlertStatus::Open)->value('id'));
        $this->assertSame($driverAlertId, Alert::query()->where('type', AlertType::PredictedDriverSafety)->where('status', AlertStatus::Open)->value('id'));
    }
}
