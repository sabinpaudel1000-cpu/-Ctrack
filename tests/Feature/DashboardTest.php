<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\RiskLevel;
use App\Enums\VehicleStatus;
use App\Models\Alert;
use App\Models\Driver;
use App\Models\MaintenanceRisk;
use App\Models\TelematicsRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_kpis_come_from_the_database(): void
    {
        $user = User::factory()->create();
        $high = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'registration_number' => 'NSW-H01']);
        Vehicle::factory()->create(['status' => VehicleStatus::Inactive, 'registration_number' => 'NSW-I01']);

        MaintenanceRisk::query()->create([
            'vehicle_id' => $high->id,
            'score' => 82,
            'level' => RiskLevel::High,
            'factors_json' => [],
            'algorithm_version' => 'weighted-baseline-v1',
            'calculated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2')
            ->assertSee('High risk')
            ->assertSee('NSW-H01')
            ->assertSee('HIGH risk')
            ->assertSee('No alerts generated yet');
    }

    public function test_dashboard_fuel_kpi_is_calculated_from_telematics(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        TelematicsRecord::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'distance_km' => 100,
            'fuel_consumed_l' => 12,
            'recorded_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('12 L/100km');
    }

    public function test_empty_dashboard_still_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No vehicles currently require attention.')
            ->assertSee('No telematics in the last 14 days.');
    }

    public function test_open_alert_count_and_recent_alerts_are_shown(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-AL1']);

        Alert::query()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'type' => AlertType::Speeding,
            'severity' => AlertSeverity::High,
            'message' => 'Synthetic speeding alert for tests',
            'status' => AlertStatus::Open,
            'triggered_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Open alerts')
            ->assertSee('Synthetic speeding alert for tests')
            ->assertSee('NSW-AL1');
    }
}
