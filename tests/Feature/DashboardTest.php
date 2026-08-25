<?php

namespace Tests\Feature;

use App\Enums\RiskLevel;
use App\Enums\VehicleStatus;
use App\Models\MaintenanceRisk;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSee('High risk');
    }
}
