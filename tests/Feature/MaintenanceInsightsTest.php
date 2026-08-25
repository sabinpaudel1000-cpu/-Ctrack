<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Enums\RiskLevel;
use App\Models\Alert;
use App\Models\MaintenanceRisk;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Alerts\AlertGenerator;
use App\Services\Recommendations\RecommendationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_risk_alerts_or_recommendations(): void
    {
        $this->get(route('risks.index'))->assertRedirect(route('login'));
        $this->get(route('alerts.index'))->assertRedirect(route('login'));
        $this->get(route('recommendations.index'))->assertRedirect(route('login'));
    }

    public function test_empty_risk_and_alert_pages_render(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('risks.index'))
            ->assertOk()
            ->assertSee('No maintenance-risk snapshots yet');

        $this->actingAs($user)
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('No alerts match those filters.');

        $this->actingAs($user)
            ->get(route('recommendations.index'))
            ->assertOk()
            ->assertSee('No recommendations yet');
    }

    public function test_high_maintenance_risk_creates_a_high_severity_alert(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-H99']);
        $this->snapshot($vehicle, 77.5, RiskLevel::High);

        $created = app(AlertGenerator::class)->regenerate();

        $this->assertTrue($created->contains(fn (Alert $alert) => $alert->type === AlertType::HighMaintenanceRisk));
        $this->assertDatabaseHas('alerts', [
            'vehicle_id' => $vehicle->id,
            'type' => AlertType::HighMaintenanceRisk->value,
            'severity' => AlertSeverity::High->value,
            'status' => AlertStatus::Open->value,
        ]);
    }

    public function test_medium_maintenance_risk_creates_a_medium_severity_alert(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-M55']);
        $this->snapshot($vehicle, 55.0, RiskLevel::Medium);

        app(AlertGenerator::class)->regenerate();

        $this->assertDatabaseHas('alerts', [
            'vehicle_id' => $vehicle->id,
            'type' => AlertType::HighMaintenanceRisk->value,
            'severity' => AlertSeverity::Medium->value,
            'status' => AlertStatus::Open->value,
        ]);
    }

    public function test_low_risk_does_not_create_a_maintenance_alert(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-L10']);
        $this->snapshot($vehicle, 12.0, RiskLevel::Low);

        app(AlertGenerator::class)->regenerate();

        $this->assertDatabaseMissing('alerts', [
            'vehicle_id' => $vehicle->id,
            'type' => AlertType::HighMaintenanceRisk->value,
        ]);
    }

    public function test_high_maintenance_alert_maps_to_a_high_priority_inspection(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-H99']);
        $this->snapshot($vehicle, 80.0, RiskLevel::High);
        app(AlertGenerator::class)->regenerate();

        $created = app(RecommendationEngine::class)->regenerate();

        $this->assertNotEmpty($created);
        $this->assertDatabaseHas('recommendations', [
            'vehicle_id' => $vehicle->id,
            'priority' => RecommendationPriority::High->value,
            'status' => RecommendationStatus::Pending->value,
        ]);
        $this->assertTrue(
            $created->contains(fn ($row) => str_contains($row->title, 'Schedule a maintenance inspection'))
        );
    }

    public function test_medium_maintenance_alert_maps_to_a_medium_priority_service_plan(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'NSW-M55']);
        $this->snapshot($vehicle, 50.0, RiskLevel::Medium);
        app(AlertGenerator::class)->regenerate();
        $created = app(RecommendationEngine::class)->regenerate();

        $this->assertDatabaseHas('recommendations', [
            'vehicle_id' => $vehicle->id,
            'priority' => RecommendationPriority::Medium->value,
            'status' => RecommendationStatus::Pending->value,
        ]);
        $this->assertTrue(
            $created->contains(fn ($row) => str_contains($row->title, 'Plan a scheduled service'))
        );
    }

    public function test_alert_status_can_be_updated(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $alert = Alert::query()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'type' => AlertType::HighMaintenanceRisk,
            'severity' => AlertSeverity::High,
            'message' => 'Test maintenance alert',
            'status' => AlertStatus::Open,
            'triggered_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('alerts.update', $alert), ['status' => AlertStatus::Resolved->value])
            ->assertRedirect();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::Resolved->value,
        ]);
    }

    private function snapshot(Vehicle $vehicle, float $score, RiskLevel $level): void
    {
        MaintenanceRisk::query()->create([
            'vehicle_id' => $vehicle->id,
            'score' => $score,
            'level' => $level,
            'factors_json' => [],
            'algorithm_version' => 'weighted-baseline-v1',
            'calculated_at' => now(),
        ]);
    }
}
