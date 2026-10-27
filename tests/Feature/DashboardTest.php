<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_are_redirected_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_referrers_can_access_the_referrer_dashboard(): void
    {
        $referrer = User::factory()->create();
        $referrer->forceFill(['type' => 'referrer'])->save();

        $this->actingAs($referrer)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_referrers_cannot_access_the_admin_dashboard(): void
    {
        $referrer = User::factory()->create();
        $referrer->forceFill(['type' => 'referrer'])->save();

        $this->actingAs($referrer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
