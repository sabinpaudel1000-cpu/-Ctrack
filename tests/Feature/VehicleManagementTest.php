<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_vehicle(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vehicles.store'), [
            'registration_number' => 'NSW-999',
            'make' => 'Toyota',
            'model' => 'HiAce',
            'vehicle_type' => VehicleType::Van->value,
            'manufacture_year' => 2022,
            'mileage' => 15000,
            'status' => VehicleStatus::Active->value,
            'driver_id' => null,
            'last_maintenance_date' => '2026-07-01',
        ])->assertRedirect(route('vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => 'NSW-999',
            'make' => 'Toyota',
        ]);
    }

    public function test_vehicle_search_filters_results(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->create(['registration_number' => 'NSW-AAA']);
        Vehicle::factory()->create(['registration_number' => 'QLD-BBB']);

        $this->actingAs($user)
            ->get(route('vehicles.index', ['q' => 'NSW-AAA']))
            ->assertOk()
            ->assertSee('NSW-AAA')
            ->assertDontSee('QLD-BBB');
    }
}
