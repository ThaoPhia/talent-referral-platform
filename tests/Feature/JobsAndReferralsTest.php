<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobsAndReferralsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.schedule_timezone' => 'America/Chicago']);
        $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'America/Chicago'));
    }

    public function test_admin_can_create_a_job(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();

        $response = $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
            'closed_on' => '2026-12-24',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('jobs', [
            'title' => 'Senior Engineer',
            'status' => 'active',
        ]);
    }

    public function test_referrer_cannot_create_a_job(): void
    {
        $referrer = User::factory()->create();

        $response = $this->actingAs($referrer)->post(route('admin.jobs.store'), [
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
            'closed_on' => '2026-12-24',
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
            'closed_on' => '2026-12-24',
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
            'closed_on' => '2026-12-24',
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

    public function test_referrer_cannot_list_jobs_from_the_admin_endpoint(): void
    {
        $referrer = User::factory()->create();

        $this->actingAs($referrer)
            ->get(route('admin.jobs.index'))
            ->assertForbidden();
    }

    public function test_referrer_can_create_a_referral(): void
    {
        $referrer = User::factory()->create();
        $referrer->forceFill(['type' => 'referrer'])->save();
        $candidate = User::factory()->create();
        $candidate->forceFill(['type' => 'candidate'])->save();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
            'closed_on' => '2026-12-24',
        ]);

        $response = $this->actingAs($referrer)->post(route('referrals.store'), [
            'candidate_name' => $candidate->name,
            'candidate_email' => $candidate->email,
            'referrer_note' => 'Strong candidate.',
            'job_id' => $job->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('referrals', [
            'user_id' => $referrer->id,
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => 'pending',
            'referrer_note' => 'Strong candidate.',
            'candidate_note' => null,
        ]);
    }

    public function test_new_referral_candidate_is_created_with_candidate_type(): void
    {
        $referrer = User::factory()->create();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
            'closed_on' => '2026-12-24',
        ]);

        $this->actingAs($referrer)->post(route('referrals.store'), [
            'candidate_name' => 'New Candidate',
            'candidate_email' => 'candidate@example.com',
            'referrer_note' => 'Strong candidate.',
            'job_id' => $job->id,
        ])->assertRedirect();

        $this->assertSame('referrer', $referrer->fresh()->type);
        $this->assertDatabaseHas('users', [
            'email' => 'candidate@example.com',
            'type' => 'candidate',
        ]);

        $this->assertDatabaseHas('referrals', [
            'user_id' => $referrer->id,
            'candidate_id' => User::query()->where('email', 'candidate@example.com')->value('id'),
        ]);

        $referral = Referral::query()->with(['referrer', 'candidate'])->firstOrFail();
        $this->assertSame($referrer->id, $referral->referrer->id);
        $this->assertSame(
            User::query()->where('email', 'candidate@example.com')->value('id'),
            $referral->candidate->id,
        );
    }

    public function test_candidate_cannot_create_a_referral(): void
    {
        $candidate = User::factory()->create();
        $candidate->forceFill(['type' => 'candidate'])->save();
        $job = Job::create([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-09-24 09:00:00',
            'closed_on' => '2026-12-24',
        ]);

        $this->actingAs($candidate)->post(route('referrals.store'), [
            'candidate_name' => 'Someone Else',
            'candidate_email' => 'someone@example.com',
            'referrer_note' => 'Strong candidate.',
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
            'closed_on' => '2026-12-24',
        ]);

        $response = $this->actingAs($admin)->post(route('referrals.store'), [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_referrer_can_only_refer_to_published_open_jobs(): void
    {
        $referrer = User::factory()->create();
        $referrer->forceFill(['type' => 'referrer'])->save();
        $job = fn (array $attributes) => Job::create(array_merge([
            'title' => 'Senior Engineer',
            'description' => 'Unpublished details.',
            'location' => 'Remote',
            'post_on' => '2026-10-01 09:00:00',
            'closed_on' => '2026-12-24',
        ], $attributes));
        $refer = fn (Job $job) => $this->actingAs($referrer)->post(route('referrals.store'), [
            'candidate_name' => 'Jane Candidate',
            'candidate_email' => 'jane@example.com',
            'referrer_note' => 'Strong candidate.',
            'job_id' => $job->id,
        ]);

        $unavailable = [
            $job(['status' => 'draft']),
            $job(['status' => 'archived']),
            $job(['post_on' => '2026-11-01 09:00:00', 'closed_on' => '2027-01-24']),
            $job(['closed_on' => '2026-10-27']),
        ];

        foreach ($unavailable as $unavailableJob) {
            $refer($unavailableJob)->assertSessionHasErrors('job_id');
        }

        $this->assertDatabaseCount('referrals', 0);

        $closesToday = $job(['closed_on' => '2026-10-28']);
        $refer($closesToday)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('referrals', ['job_id' => $closesToday->id]);
    }
}
