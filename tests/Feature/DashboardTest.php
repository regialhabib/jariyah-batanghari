<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_nazir_is_redirected_to_nazir_dashboard(): void
    {
        $user = User::factory()->nazir()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('nazir.dashboard'));
        $this->actingAs($user)->get(route('nazir.dashboard'))->assertOk();
    }

    public function test_petugas_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->bpn()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }
}
