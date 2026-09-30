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

    public function test_members_can_access_the_member_dashboard(): void
    {
        $member = User::factory()->create();
        $member->forceFill(['type' => 'referrer'])->save();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_members_cannot_access_the_admin_dashboard(): void
    {
        $member = User::factory()->create();
        $member->forceFill(['type' => 'referrer'])->save();

        $this->actingAs($member)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
