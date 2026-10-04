<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_visible_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ctrack Fleet BI')
            ->assertSee('Sign in to continue')
            ->assertSee('manager@demo.local');
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_fleet_manager_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@example.test',
        ]);

        $this->post(route('login.store'), [
            'email' => 'manager@example.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
