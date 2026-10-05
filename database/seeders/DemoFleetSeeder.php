<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\MaintenanceServiceType;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\TelematicsRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FleetInsightRecalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Synthetic demo data only. This is not Ctrack customer data.
 */
class DemoFleetSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->create([
            'name' => 'Alex Rivera',
            'email' => 'manager@demo.local',
            'password' => Hash::make('password'),
            'role' => UserRole::FleetManager,
            'email_verified_at' => now(),
        ]);

        User::query()->create([
            'name' => 'Jordan Lee',
            'email' => 'admin@demo.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        // The analytics and prediction screens only read the last 7 or 30 days.
        // A fixed August date leaves those screens empty later in the semester.
        $now = now();

        foreach ($this->fleet() as $index => $row) {
            $driver = Driver::query()->create($row['driver']);
            $vehicle = Vehicle::query()->create(array_merge($row['vehicle'], [
                'driver_id' => $row['vehicle']['status'] === VehicleStatus::Inactive ? null : $driver->id,
            ]));

            if ($vehicle->last_maintenance_date) {
                MaintenanceRecord::query()->create([
                    'vehicle_id' => $vehicle->id,
                    'service_date' => $vehicle->last_maintenance_date,
                    'service_type' => MaintenanceServiceType::Scheduled,
                    'description' => 'Synthetic scheduled service record',
                    'odometer' => max(1000, $vehicle->mileage - 8000),
                    'cost' => 420 + ($index * 15),
                ]);
            }

            $this->seedTelematics($vehicle, $driver, $row['profile'], $now);
        }

        app(FleetInsightRecalculator::class)->recalculate();
    }

    /**
     * @return list<array{driver: array<string, mixed>, vehicle: array<string, mixed>, profile: string}>
     */
    private function fleet(): array
    {
        return [
            $this->pair('DRV-1001', 'Priya', 'Sharma', 'NSW-101', 'Toyota', 'HiAce', VehicleType::Van, 2023, 28000, '2026-07-12', 'clean'),
            $this->pair('DRV-1002', 'James', 'Nguyen', 'NSW-102', 'Ford', 'Transit', VehicleType::Van, 2022, 41000, '2026-06-02', 'clean'),
            $this->pair('DRV-1003', 'Amelia', 'Walsh', 'NSW-103', 'Toyota', 'HiLux', VehicleType::Ute, 2021, 62000, '2026-05-18', 'average'),
            $this->pair('DRV-1004', 'Daniel', 'Okoro', 'NSW-104', 'Isuzu', 'NPR', VehicleType::Truck, 2020, 98000, '2026-04-09', 'average'),
            $this->pair('DRV-1005', 'Sofia', 'Martinez', 'NSW-105', 'Mitsubishi', 'Express', VehicleType::Van, 2019, 121000, '2026-03-01', 'average'),
            $this->pair('DRV-1006', 'Liam', 'Patel', 'NSW-106', 'Ford', 'Ranger', VehicleType::Ute, 2018, 155000, '2025-12-11', 'harsh'),
            $this->pair('DRV-1007', 'Chloe', 'Bennett', 'NSW-107', 'Toyota', 'Coaster', VehicleType::Van, 2017, 188000, '2025-09-20', 'average'),
            $this->pair('DRV-1008', 'Noah', 'Singh', 'NSW-108', 'Hino', '300', VehicleType::Truck, 2016, 214000, '2025-08-01', 'speeding'),
            $this->pair('DRV-1009', 'Grace', 'Olsen', 'NSW-109', 'Mitsubishi', 'Triton', VehicleType::Ute, 2015, 236000, '2025-06-14', 'harsh'),
            $this->pair('DRV-1010', 'Ethan', 'Clarke', 'NSW-110', 'Isuzu', 'NQR', VehicleType::Truck, 2014, 268000, '2025-07-20', 'hot'),
            $this->pair('DRV-1011', 'Mia', 'Rahman', 'NSW-111', 'Ford', 'Transit', VehicleType::Van, 2013, 291000, '2025-02-03', 'hot'),
            $this->pair('DRV-1012', 'Jack', 'Thompson', 'NSW-112', 'Toyota', 'HiAce', VehicleType::Van, 2012, 318000, '2024-11-19', 'harsh'),
            $this->pair('DRV-1013', 'Ava', 'Nguyen', 'NSW-113', 'Holden', 'Colorado', VehicleType::Ute, 2024, 12000, '2026-08-01', 'clean'),
            $this->pair('DRV-1014', 'Leo', 'Karim', 'NSW-114', 'Mercedes', 'Sprinter', VehicleType::Van, 2023, 19000, '2026-07-28', 'clean'),
            $this->pair('DRV-1015', 'Ruby', 'Hassan', 'NSW-115', 'Isuzu', 'NPR', VehicleType::Truck, 2011, 340000, '2024-08-12', 'hot', VehicleStatus::Maintenance),
            $this->pair('DRV-1016', 'Oscar', 'Flynn', 'NSW-116', 'Toyota', 'HiLux', VehicleType::Ute, 2010, 362000, '2024-04-02', 'average', VehicleStatus::Inactive),
        ];
    }

    private function pair(
        string $code,
        string $first,
        string $last,
        string $reg,
        string $make,
        string $model,
        VehicleType $type,
        int $year,
        int $mileage,
        string $serviceDate,
        string $profile,
        VehicleStatus $status = VehicleStatus::Active,
    ): array {
        return [
            'profile' => $profile,
            'driver' => [
                'employee_code' => $code,
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower($first).'.'.strtolower($last).'@demo.local',
                'phone' => '04'.substr(preg_replace('/\D/', '', $code).'00000000', 0, 8),
                'licence_number' => strtoupper(substr($last, 0, 2)).substr($code, -4).'NSW',
                'licence_expiry' => '2028-03-31',
                'status' => $status === VehicleStatus::Inactive ? DriverStatus::Inactive : DriverStatus::Active,
                'hire_date' => '2019-03-01',
            ],
            'vehicle' => [
                'registration_number' => $reg,
                'make' => $make,
                'model' => $model,
                'vehicle_type' => $type,
                'manufacture_year' => $year,
                'mileage' => $mileage,
                'status' => $status,
                'last_maintenance_date' => $serviceDate,
            ],
        ];
    }

    private function seedTelematics(Vehicle $vehicle, Driver $driver, string $profile, Carbon $now): void
    {
        $baseLat = -33.8688;
        $baseLng = 151.2093;
        $id = (int) $vehicle->id;

        for ($day = 30; $day >= 1; $day--) {
            for ($ping = 0; $ping < 5; $ping++) {
                $at = $now->copy()->subDays($day)->setTime(7 + $ping * 2, ($id * 3) % 50);
                $distance = 8 + (($id + $day + $ping) % 12);
                $fuelRate = match ($profile) {
                    'hot' => 0.16,
                    'harsh', 'speeding' => 0.14,
                    'average' => 0.11,
                    default => 0.08,
                };
                $temp = match ($profile) {
                    'hot' => 108 + (($day + $ping) % 6),
                    'harsh' => 96,
                    default => 88 + (($id + $ping) % 6),
                };
                $speed = match ($profile) {
                    'speeding' => 78 + (($ping * 7) % 30),
                    'harsh' => 55 + ($ping * 5),
                    default => 42 + (($id + $ping) % 18),
                };

                TelematicsRecord::query()->create([
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $vehicle->status === VehicleStatus::Inactive ? null : $driver->id,
                    'recorded_at' => $at,
                    'latitude' => $baseLat + ($id * 0.004) + ($ping * 0.001),
                    'longitude' => $baseLng + ($id * 0.003) - ($ping * 0.001),
                    'speed' => $speed,
                    'distance_km' => $distance,
                    'fuel_consumed_l' => round($distance * $fuelRate, 2),
                    'engine_temperature' => $temp,
                    'harsh_acceleration' => in_array($profile, ['harsh', 'hot'], true) && ($ping === 1 || $day % 4 === 0),
                    'harsh_braking' => in_array($profile, ['harsh', 'speeding'], true) && ($ping === 3 || $day % 5 === 0),
                    'speeding' => $profile === 'speeding' && ($ping === 4 || $day % 3 === 0),
                ]);
            }
        }
    }
}
