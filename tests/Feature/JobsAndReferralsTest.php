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

        $this->actingAs($admin)->get(route('admin.jobs.edit', $job))->assertOk();
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

    public function test_recruiter_cannot_create_edit_or_update_jobs(): void
    {
        $recruiter = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $this->actingAs($recruiter)->get(route('admin.jobs.create'))->assertForbidden();
        $this->get(route('admin.jobs.edit', $job))->assertForbidden();
        $this->patch(route('admin.jobs.update', $job), ['status' => 'archived'])->assertForbidden();
        $this->assertSame('active', $job->fresh()->status);
    }

    public function test_only_admin_can_list_and_update_referrals(): void
    {
        $recruiter = User::factory()->create();
        $candidate = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);
        $referral = Referral::create([
            'user_id' => $recruiter->id,
            'referrer_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => 'pending',
        ]);

        $this->actingAs($recruiter)->get(route('admin.referrals.index'))->assertForbidden();
        $this->patch(route('admin.referrals.update', $referral), ['status' => 'accepted'])->assertForbidden();
        $this->assertSame('pending', $referral->fresh()->status);

        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();
        $this->actingAs($admin)->get(route('admin.referrals.index'))->assertOk();
        $this->patch(route('admin.referrals.update', $referral), ['status' => 'accepted'])->assertRedirect();
        $this->assertSame('accepted', $referral->fresh()->status);
    }

    public function test_member_can_create_a_referral(): void
    {
        $member = User::factory()->create();
        $member->forceFill(['type' => 'recruiter'])->save();
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
            'user_id' => $member->id,
            'referrer_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => 'pending',
        ]);
        $referral = Referral::query()->firstOrFail();
        $this->assertTrue($referral->recruiter->is($member));
        $this->assertTrue($referral->candidate->is($candidate));
    }

    public function test_new_referral_candidate_is_created_as_normal_user(): void
    {
        $referrer = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $this->actingAs($referrer)->post(route('referrals.store'), [
            'candidate_name' => 'New Candidate',
            'candidate_email' => 'candidate@example.com',
            'resume_url' => 'https://example.com/resume.pdf',
            'note' => 'Strong candidate.',
            'job_id' => $job->id,
        ])->assertRedirect();

        $this->assertSame('recruiter', $referrer->fresh()->type);
        $this->assertDatabaseHas('users', [
            'email' => 'candidate@example.com',
            'type' => 'normal',
        ]);
    }

    public function test_normal_user_cannot_create_a_referral(): void
    {
        $candidate = User::factory()->create();
        $candidate->forceFill(['type' => 'normal'])->save();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
        ]);

        $this->actingAs($candidate)->post(route('referrals.store'), [
            'candidate_name' => 'Someone Else',
            'candidate_email' => 'someone@example.com',
            'resume_url' => 'https://example.com/resume.pdf',
            'note' => 'Strong candidate.',
            'job_id' => $job->id,
        ])->assertForbidden();
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
