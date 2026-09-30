<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobsAndReferralsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_job(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();

        $response = $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('jobs', [
            'title' => 'Senior Engineer',
            'status' => 'active',
        ]);
    }

    public function test_member_cannot_create_a_job(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->post(route('admin.jobs.store'), [
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_admin_can_list_jobs(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();
        Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.jobs.index'))
            ->assertOk();
    }

    public function test_admin_can_update_a_job(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.jobs.update', $job), [
            'status' => 'archived',
        ]);

        $response->assertRedirect(route('admin.jobs.index'));
        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'status' => 'archived',
        ]);
    }

    public function test_member_cannot_list_jobs_from_the_admin_endpoint(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.jobs.index'))
            ->assertForbidden();
    }

    public function test_member_can_create_a_referral(): void
    {
        $member = User::factory()->create();
        $member->forceFill(['type' => 'normal'])->save();
        $candidate = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $response = $this->actingAs($member)->post(route('referrals.store'), [
            'candidate_name' => $candidate->name,
            'candidate_email' => $candidate->email,
            'resume_url' => 'https://example.com/resume.pdf',
            'note' => 'Strong candidate.',
            'job_id' => $job->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('referrals', [
            'user_id' => $candidate->id,
            'referrer_id' => $member->id,
            'job_id' => $job->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_cannot_create_a_referral(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();
        $candidate = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('referrals.store'), [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('referrals', 0);
    }
}
