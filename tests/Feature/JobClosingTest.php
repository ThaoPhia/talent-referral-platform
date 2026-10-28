<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobClosingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['type' => 'admin'])->save();

        return $admin;
    }

    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-11-30 09:00:00',
            'closed_date' => '2027-02-28',
        ], $overrides);
    }

    public function test_admin_can_create_a_draft_job_with_a_close_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jobs.store'), $this->jobPayload(['status' => 'draft']))
            ->assertRedirect(route('admin.jobs.index'));

        $this->assertDatabaseHas('jobs', [
            'title' => 'Senior Engineer',
            'status' => 'draft',
            'closed_date' => '2027-02-28',
        ]);
    }

    public function test_close_date_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jobs.store'), $this->jobPayload(['closed_date' => null]))
            ->assertSessionHasErrors('closed_date');

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_close_date_cannot_be_before_post_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jobs.store'), $this->jobPayload(['closed_date' => '2026-11-29']))
            ->assertSessionHasErrors('closed_date');
    }

    public function test_close_date_cannot_be_more_than_three_months_after_post_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jobs.store'), $this->jobPayload(['closed_date' => '2027-03-01']))
            ->assertSessionHasErrors('closed_date');
    }

    public function test_malformed_post_date_returns_validation_error_instead_of_server_error(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jobs.store'), $this->jobPayload(['post_on' => ['invalid']]))
            ->assertSessionHasErrors('post_on');

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_updating_post_date_revalidates_existing_close_date(): void
    {
        $job = Job::create($this->jobPayload());

        $this->actingAs($this->admin())
            ->patch(route('admin.jobs.update', $job), ['post_on' => '2026-11-15 09:00:00'])
            ->assertSessionHasErrors('closed_date');

        $this->actingAs($this->admin())
            ->patch(route('admin.jobs.update', $job), ['closed_date' => '2027-03-01'])
            ->assertSessionHasErrors('closed_date');

        $this->actingAs($this->admin())
            ->patch(route('admin.jobs.update', $job), ['closed_date' => '2027-01-15'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-01-15', $job->fresh()->closed_date->toDateString());
    }

    public function test_archive_command_only_archives_expired_active_jobs(): void
    {
        $this->travelTo('2027-01-15 23:59:00');

        $expired = Job::create($this->jobPayload(['title' => 'Expired', 'closed_date' => '2027-01-14']));
        $closingToday = Job::create($this->jobPayload(['title' => 'Closing today', 'closed_date' => '2027-01-15']));
        $open = Job::create($this->jobPayload(['title' => 'Open', 'closed_date' => '2027-01-16']));
        $expiredDraft = Job::create($this->jobPayload(['title' => 'Draft', 'closed_date' => '2027-01-14', 'status' => 'draft']));

        $this->artisan('jobs:archive-expired')->assertSuccessful();

        $this->assertSame('archived', $expired->fresh()->status);
        $this->assertSame('archived', $closingToday->fresh()->status);
        $this->assertSame('active', $open->fresh()->status);
        $this->assertSame('draft', $expiredDraft->fresh()->status);
    }

    public function test_archive_command_is_scheduled_daily_at_end_of_day(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'jobs:archive-expired'));

        $this->assertNotNull($event);
        $this->assertSame('59 23 * * *', $event->expression);
        $this->assertSame(config('app.schedule_timezone'), $event->timezone);
    }

    public function test_archive_command_uses_schedule_timezone_for_today(): void
    {
        config(['app.schedule_timezone' => 'America/Chicago']);
        $this->travelTo(now('UTC')->setDate(2027, 1, 15)->setTime(4, 59));

        $closesTodayInChicago = Job::create($this->jobPayload(['title' => 'Chicago today', 'post_on' => '2026-12-01 09:00:00', 'closed_date' => '2027-01-14']));
        $closesTodayInUtc = Job::create($this->jobPayload(['title' => 'UTC today', 'post_on' => '2026-12-01 09:00:00', 'closed_date' => '2027-01-15']));

        $this->artisan('jobs:archive-expired')->assertSuccessful();

        $this->assertSame('archived', $closesTodayInChicago->fresh()->status);
        $this->assertSame('active', $closesTodayInUtc->fresh()->status);
    }
}
