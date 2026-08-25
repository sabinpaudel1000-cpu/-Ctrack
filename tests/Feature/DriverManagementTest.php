<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_drivers(): void
    {
        $this->get(route('drivers.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_a_driver(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drivers.store'), $this->validPayload())
            ->assertRedirect(route('drivers.index'));

        $this->assertDatabaseHas('drivers', [
            'employee_code' => 'DRV-9001',
            'first_name' => 'Sam',
            'last_name' => 'Patel',
        ]);
    }

    public function test_employee_code_is_stored_uppercase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drivers.store'), $this->validPayload([
                'employee_code' => ' drv-9002 ',
            ]))
            ->assertRedirect(route('drivers.index'));

        $this->assertDatabaseHas('drivers', [
            'employee_code' => 'DRV-9002',
        ]);
    }

    public function test_duplicate_employee_code_is_rejected(): void
    {
        $user = User::factory()->create();
        Driver::factory()->create(['employee_code' => 'DRV-1111']);

        $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), $this->validPayload([
                'employee_code' => 'DRV-1111',
            ]))
            ->assertRedirect(route('drivers.create'))
            ->assertSessionHasErrors('employee_code');
    }

    public function test_future_hire_date_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), $this->validPayload([
                'hire_date' => now()->addDay()->toDateString(),
            ]))
            ->assertRedirect(route('drivers.create'))
            ->assertSessionHasErrors('hire_date');
    }

    public function test_inactive_driver_cannot_be_assigned_a_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => null]);

        $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), $this->validPayload([
                'status' => DriverStatus::Inactive->value,
                'vehicle_id' => $vehicle->id,
            ]))
            ->assertRedirect(route('drivers.create'))
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_vehicle_already_assigned_to_another_driver_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => $other->id]);

        $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), $this->validPayload([
                'vehicle_id' => $vehicle->id,
            ]))
            ->assertRedirect(route('drivers.create'))
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_driver_can_be_assigned_an_unassigned_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => null]);

        $this->actingAs($user)
            ->post(route('drivers.store'), $this->validPayload([
                'vehicle_id' => $vehicle->id,
            ]))
            ->assertRedirect(route('drivers.index'));

        $driver = Driver::query()->where('employee_code', 'DRV-9001')->first();
        $this->assertNotNull($driver);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_driver_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['first_name' => 'Alex']);
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id, 'registration_number' => 'NSW-DEL']);

        $this->actingAs($user)
            ->put(route('drivers.update', $driver), $this->validPayload([
                'employee_code' => $driver->employee_code,
                'first_name' => 'Alexa',
                'last_name' => $driver->last_name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'licence_number' => $driver->licence_number,
                'licence_expiry' => optional($driver->licence_expiry)?->toDateString(),
                'status' => $driver->status->value,
                'hire_date' => optional($driver->hire_date)?->toDateString(),
                'vehicle_id' => $vehicle->id,
            ]))
            ->assertRedirect(route('drivers.show', $driver));

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'first_name' => 'Alexa',
        ]);

        $this->actingAs($user)
            ->delete(route('drivers.destroy', $driver))
            ->assertRedirect(route('drivers.index'));

        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'driver_id' => null,
        ]);
    }

    public function test_driver_search_filters_results(): void
    {
        $user = User::factory()->create();
        Driver::factory()->create(['first_name' => 'Anita', 'last_name' => 'Singh', 'employee_code' => 'DRV-1111']);
        Driver::factory()->create(['first_name' => 'Ben', 'last_name' => 'Cole', 'employee_code' => 'DRV-2222']);

        $this->actingAs($user)
            ->get(route('drivers.index', ['q' => 'Anita']))
            ->assertOk()
            ->assertSee('Anita Singh')
            ->assertDontSee('Ben Cole');
    }

    public function test_assignment_filter_shows_unassigned_drivers(): void
    {
        $user = User::factory()->create();
        $assigned = Driver::factory()->create(['first_name' => 'Kara', 'last_name' => 'Fleet']);
        Driver::factory()->create(['first_name' => 'Omar', 'last_name' => 'Spare']);
        Vehicle::factory()->create(['driver_id' => $assigned->id]);

        $this->actingAs($user)
            ->get(route('drivers.index', ['assignment' => 'unassigned']))
            ->assertOk()
            ->assertSee('Omar Spare')
            ->assertDontSee('Kara Fleet');
    }

    public function test_empty_driver_list_shows_a_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('drivers.index'))
            ->assertOk()
            ->assertSee('No drivers match those filters.');
    }

    public function test_driver_detail_shows_contact_and_expired_licence_warning(): void
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create([
            'first_name' => 'Nina',
            'last_name' => 'Hart',
            'email' => 'nina.hart@demo.local',
            'phone' => '0411222333',
            'licence_expiry' => now()->subDays(10)->toDateString(),
            'status' => DriverStatus::Active,
        ]);

        $this->actingAs($user)
            ->get(route('drivers.show', $driver))
            ->assertOk()
            ->assertSee('nina.hart@demo.local')
            ->assertSee('0411222333')
            ->assertSee('Licence expired');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 'DRV-9001',
            'first_name' => 'Sam',
            'last_name' => 'Patel',
            'email' => 'sam.patel@demo.local',
            'phone' => '0411000000',
            'licence_number' => 'NSW9001',
            'licence_expiry' => now()->addYears(2)->toDateString(),
            'status' => DriverStatus::Active->value,
            'hire_date' => now()->subYears(2)->toDateString(),
            'vehicle_id' => null,
        ], $overrides);
    }
}
