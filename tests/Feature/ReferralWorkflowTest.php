<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralInvitation;
use App\Notifications\ReferralResponded;
use App\Support\ReferralInvitationToken;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReferralWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.schedule_timezone' => 'America/Chicago']);
        // Travel in UTC: Carbon reads DB timestamps in the test clock's timezone.
        $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'America/Chicago')->utc());
    }

    private function user(string $type, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['type' => $type])->save();

        return $user;
    }

    private function job(array $attributes = []): Job
    {
        return Job::create(array_merge([
            'title' => 'Senior Engineer',
            'description' => 'Build great things.',
            'location' => 'Remote',
            'post_on' => '2026-10-01 09:00:00',
            'closed_on' => '2026-12-24',
        ], $attributes));
    }

    private function referral(array $attributes = [], ?Job $job = null): Referral
    {
        return Referral::create(array_merge([
            'user_id' => $this->user('referrer')->id,
            'candidate_id' => $this->user('candidate')->id,
            'job_id' => ($job ?? $this->job())->id,
            'status' => 'pending',
            'referrer_note' => 'Great fit for this team.',
        ], $attributes));
    }

    private function token(Referral $referral): string
    {
        return ReferralInvitationToken::encode($referral);
    }

    private function referralPayload(Job $job, array $overrides = []): array
    {
        return array_merge([
            'candidate_name' => 'Jane Candidate',
            'candidate_email' => 'jane@example.com',
            'referrer_note' => 'Strong candidate.',
            'job_id' => $job->id,
        ], $overrides);
    }

    public function test_creating_a_referral_emails_the_candidate_an_encrypted_job_link(): void
    {
        Notification::fake();
        $referrer = $this->user('referrer', ['name' => 'Riley Referrer']);
        $job = $this->job();

        $this->actingAs($referrer)
            ->post(route('referrals.store'), $this->referralPayload($job))
            ->assertSessionHasNoErrors();

        $candidate = User::query()->where('email', 'jane@example.com')->firstOrFail();

        Notification::assertSentTo($candidate, ReferralInvitation::class, function (ReferralInvitation $notification) use ($candidate, $job) {
            $mail = $notification->toMail($candidate);
            $token = basename((string) parse_url((string) $mail->actionUrl, PHP_URL_PATH));

            $this->assertSame('Riley Referrer referred you for Senior Engineer', $mail->subject);
            $this->assertContains('Strong candidate.', $mail->introLines);
            $this->assertStringNotContainsString('job_id', (string) base64_decode(strtr($token, '-_', '+/')));
            $this->assertSame(
                ['job_id' => $job->id, 'candidate_id' => $candidate->id],
                ReferralInvitationToken::decode($token),
            );

            return true;
        });
    }

    public function test_referrer_note_is_optional(): void
    {
        Notification::fake();
        $candidateEmail = 'no-note@example.com';

        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($this->job(), ['candidate_email' => $candidateEmail, 'referrer_note' => '']))
            ->assertSessionHasNoErrors();

        $referral = Referral::query()->sole();
        $this->assertNull($referral->referrer_note);

        $mail = (new ReferralInvitation($referral))->toMail($referral->candidate);
        $this->assertNotContains('A note from ' . $referral->referrer->name . ':', $mail->introLines);
    }

    public function test_referrer_note_is_limited_in_length(): void
    {
        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($this->job(), ['referrer_note' => str_repeat('a', 5001)]))
            ->assertSessionHasErrors('referrer_note');

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_referral_is_kept_when_the_invitation_email_fails(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);
        $this->withoutExceptionHandling();

        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($this->job()))
            ->assertRedirect();

        $this->assertDatabaseCount('referrals', 1);
    }

    public function test_a_candidate_cannot_be_referred_to_the_same_job_twice(): void
    {
        Notification::fake();
        $job = $this->job();

        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($job))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($job, ['candidate_name' => 'Jane Again']))
            ->assertSessionHasErrors(['candidate_email' => 'This candidate has already been referred to this job.']);

        $this->actingAs($this->user('referrer'))
            ->post(route('referrals.store'), $this->referralPayload($this->job(['title' => 'Designer'])))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('referrals', 2);
        Notification::assertSentTimes(ReferralInvitation::class, 2);
    }

    public function test_invalid_or_tampered_tokens_are_not_found(): void
    {
        $referral = $this->referral();
        $forged = rtrim(strtr(base64_encode(json_encode(['job_id' => $referral->job_id, 'candidate_id' => $referral->candidate_id])), '+/', '-_'), '=');
        $wrongPair = rtrim(strtr(base64_encode(Crypt::encryptString(json_encode(['job_id' => $referral->job_id, 'candidate_id' => 999]))), '+/', '-_'), '=');

        foreach (['not-a-token', $forged, substr($this->token($referral), 0, -4) . 'AAAA', $wrongPair] as $token) {
            $this->get(route('referrals.invitation.show', ['token' => $token]))->assertNotFound();
            $this->post(route('referrals.invitation.respond', ['token' => $token]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf'])->assertNotFound();
        }

        $this->assertNull($referral->fresh()->viewed_at);
        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_candidate_can_view_the_job_without_logging_in_and_viewed_at_is_recorded_once(): void
    {
        $referral = $this->referral();
        $token = $this->token($referral);

        $this->get(route('referrals.invitation.show', ['token' => $token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Referrals/Invitation')
                ->where('token', $token)
                ->where('job.title', 'Senior Engineer')
                ->where('job.description', 'Build great things.')
                ->where('referral.status', 'pending')
                ->where('referral.can_respond', true)
                ->where('referral.referrer_name', $referral->referrer->name)
                ->where('referral.referrer_note', 'Great fit for this team.')
                ->where('referral.candidate_note', null));

        $firstViewedAt = $referral->fresh()->viewed_at;
        $this->assertEquals(now()->toImmutable(), $firstViewedAt);

        $this->travel(2)->hours();
        $this->get(route('referrals.invitation.show', ['token' => $token]))->assertOk();

        $this->assertEquals($firstViewedAt, $referral->fresh()->viewed_at);
    }

    public function test_candidate_can_accept_and_the_referrer_is_alerted_by_email_and_in_app(): void
    {
        Notification::fake();
        $referral = $this->referral();
        $token = $this->token($referral);

        $this->post(route('referrals.invitation.respond', ['token' => $token]), ['decision' => 'accepted', 'candidate_note' => 'Happy to chat!', 'resume_url' => 'https://example.com/resume.pdf'])
            ->assertRedirect(route('referrals.invitation.show', ['token' => $token]));

        $referral->refresh();
        $this->assertSame('accepted', $referral->status);
        $this->assertSame('Happy to chat!', $referral->candidate_note);
        $this->assertSame('https://example.com/resume.pdf', $referral->candidate_resume);
        $this->assertSame('link', $referral->resume_type);
        $this->assertEquals(now()->toImmutable(), $referral->responded_at);
        $this->assertNotNull($referral->viewed_at);

        Notification::assertSentTo($referral->referrer, ReferralResponded::class, function (ReferralResponded $notification, array $channels) use ($referral) {
            $this->assertEqualsCanonicalizing(['mail', 'database'], $channels);
            $mail = $notification->toMail($referral->referrer);
            $this->assertSame("{$referral->candidate->name} accepted your referral for Senior Engineer", $mail->subject);
            $this->assertContains('Happy to chat!', $mail->introLines);
            $this->assertSame('Happy to chat!', $notification->toArray($referral->referrer)['candidate_note']);

            return true;
        });
        Notification::assertNotSentTo($referral->candidate, ReferralResponded::class);
    }

    public function test_candidate_can_reject_and_the_referrer_is_alerted(): void
    {
        Notification::fake();
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'rejected'])
            ->assertRedirect();

        $this->assertSame('rejected', $referral->fresh()->status);
        $this->assertNull($referral->fresh()->candidate_note);
        $this->assertNull($referral->fresh()->candidate_resume);
        Notification::assertSentTo($referral->referrer, ReferralResponded::class, fn (ReferralResponded $notification) => str_contains(
            $notification->toArray($referral->referrer)['message'],
            'declined your referral',
        ));
    }

    public function test_declining_ignores_any_resume_that_was_sent(): void
    {
        Storage::fake(Referral::RESUME_DISK);
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), [
            'decision' => 'rejected',
            'resume_file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertNull($referral->fresh()->candidate_resume);
        $this->assertEmpty(Storage::disk(Referral::RESUME_DISK)->allFiles());
    }

    public function test_accepting_requires_a_resume_link_or_file(): void
    {
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted'])
            ->assertSessionHasErrors(['resume_url' => 'Add a link to your resume or upload a file to accept this referral.']);

        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_resume_link_must_be_an_http_url(): void
    {
        $referral = $this->referral();

        foreach (['not a url', 'javascript:alert(1)', 'ftp://example.com/cv.pdf'] as $url) {
            $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => $url])
                ->assertSessionHasErrors('resume_url');
        }

        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_candidate_can_accept_by_uploading_a_resume_file(): void
    {
        Notification::fake();
        Storage::fake(Referral::RESUME_DISK);
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), [
            'decision' => 'accepted',
            'resume_file' => UploadedFile::fake()->create('my cv.docx', 300, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $referral->refresh();
        $this->assertSame('accepted', $referral->status);
        $this->assertSame('file', $referral->resume_type);
        $this->assertStringStartsWith("{$referral->id}/", (string) $referral->candidate_resume);
        Storage::disk(Referral::RESUME_DISK)->assertExists((string) $referral->candidate_resume);
    }

    public function test_resume_file_type_and_size_are_validated(): void
    {
        Storage::fake(Referral::RESUME_DISK);
        $referral = $this->referral();
        $files = [
            UploadedFile::fake()->create('cv.exe', 100, 'application/x-msdownload'),
            UploadedFile::fake()->create('cv.html', 100, 'text/html'),
            UploadedFile::fake()->create('cv.pdf', 2049, 'application/pdf'),
        ];

        foreach ($files as $file) {
            $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_file' => $file])
                ->assertSessionHasErrors('resume_file');
        }

        $this->assertSame('pending', $referral->fresh()->status);
        $this->assertEmpty(Storage::disk(Referral::RESUME_DISK)->allFiles());
    }

    public function test_resume_link_and_file_cannot_both_be_sent(): void
    {
        Storage::fake(Referral::RESUME_DISK);
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), [
            'decision' => 'accepted',
            'resume_url' => 'https://example.com/resume.pdf',
            'resume_file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('resume_url');

        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_uploaded_resume_can_only_be_downloaded_by_admins_and_the_referrer(): void
    {
        Storage::fake(Referral::RESUME_DISK);
        Storage::disk(Referral::RESUME_DISK)->put('1/abc.pdf', 'resume contents');
        $referral = $this->referral(['status' => 'accepted', 'candidate_resume' => '1/abc.pdf']);
        $referral->candidate->forceFill(['name' => 'Jane Candidate'])->save();
        $url = route('referrals.resume', $referral);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->user('referrer'))->get($url)->assertForbidden();
        $this->actingAs($referral->candidate)->get($url)->assertForbidden();

        $this->actingAs($referral->referrer)->get($url)
            ->assertOk()
            ->assertDownload('jane-candidate-resume.pdf');
        $this->actingAs($this->user('admin'))->get($url)->assertOk();
    }

    public function test_resume_link_redirects_and_missing_resumes_are_not_found(): void
    {
        Storage::fake(Referral::RESUME_DISK);
        $linked = $this->referral(['status' => 'accepted', 'candidate_resume' => 'https://example.com/resume.pdf']);
        $missingFile = $this->referral(['status' => 'accepted', 'candidate_resume' => '9/gone.pdf'], $this->job(['title' => 'Other']));
        $none = $this->referral([], $this->job(['title' => 'Third']));
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('referrals.resume', $linked))->assertRedirect('https://example.com/resume.pdf');
        $this->actingAs($admin)->get(route('referrals.resume', $missingFile))->assertNotFound();
        $this->actingAs($admin)->get(route('referrals.resume', $none))->assertNotFound();
    }

    public function test_referral_lists_expose_a_resume_link_but_not_the_storage_path(): void
    {
        $referral = $this->referral(['status' => 'accepted', 'candidate_resume' => '1/abc.pdf']);

        $this->actingAs($referral->referrer)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('referrals.0.resume_type', 'file')
                ->where('referrals.0.resume_link', route('referrals.resume', $referral))
                ->missing('referrals.0.candidate_resume'));
    }

    public function test_in_app_alert_is_stored_even_when_the_email_fails(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf'])
            ->assertRedirect();

        $this->assertSame('accepted', $referral->fresh()->status);
        $this->assertSame(1, $referral->referrer->unreadNotifications()->count());
    }

    public function test_candidate_can_only_respond_once(): void
    {
        Notification::fake();
        $referral = $this->referral();
        $token = $this->token($referral);

        $this->post(route('referrals.invitation.respond', ['token' => $token]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf']);
        $this->post(route('referrals.invitation.respond', ['token' => $token]), ['decision' => 'rejected']);

        $this->assertSame('accepted', $referral->fresh()->status);
        Notification::assertSentTimes(ReferralResponded::class, 1);

        $this->get(route('referrals.invitation.show', ['token' => $token]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('referral.status', 'accepted')
                ->where('referral.can_respond', false));
    }

    public function test_candidate_note_is_limited_in_length(): void
    {
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'candidate_note' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('candidate_note');

        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_decision_must_be_accept_or_reject(): void
    {
        $referral = $this->referral();

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'maybe'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending', $referral->fresh()->status);
    }

    public function test_closed_jobs_can_be_viewed_but_not_responded_to(): void
    {
        Notification::fake();
        $closed = $this->referral([], $this->job(['closed_on' => '2026-10-27']));
        $archived = $this->referral([], $this->job(['title' => 'Archived role', 'status' => 'archived']));
        $draft = $this->referral([], $this->job(['title' => 'Draft role', 'status' => 'draft', 'description' => 'Secret plans.']));

        foreach ([$closed, $archived, $draft] as $referral) {
            $this->get(route('referrals.invitation.show', ['token' => $this->token($referral)]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('referral.can_respond', false));

            $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf'])
                ->assertRedirect();

            $this->assertSame('pending', $referral->fresh()->status);
            $this->assertNotNull($referral->fresh()->viewed_at);
        }

        $this->get(route('referrals.invitation.show', ['token' => $this->token($draft)]))
            ->assertInertia(fn (Assert $page) => $page->where('job.description', null));

        Notification::assertNothingSent();
    }

    public function test_job_closing_today_still_accepts_responses(): void
    {
        $referral = $this->referral([], $this->job(['closed_on' => '2026-10-28']));

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf']);

        $this->assertSame('accepted', $referral->fresh()->status);
    }

    public function test_admin_can_reopen_a_referral_so_the_candidate_can_respond_again(): void
    {
        Notification::fake();
        $referral = $this->referral(['status' => 'rejected', 'responded_at' => now()]);

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.referrals.update', $referral), ['status' => 'pending'])
            ->assertSessionHasNoErrors();

        $this->assertNull($referral->fresh()->responded_at);

        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf']);

        $this->assertSame('accepted', $referral->fresh()->status);
    }

    public function test_referrer_dashboard_shows_stats_for_their_own_referrals(): void
    {
        $referrer = $this->user('referrer');
        $job = $this->job();
        foreach ([['pending', null], ['pending', now()], ['accepted', now()], ['rejected', now()]] as $index => [$status, $viewedAt]) {
            $this->referral([
                'user_id' => $referrer->id,
                'job_id' => $this->job(['title' => "Job {$index}"])->id,
                'status' => $status,
                'viewed_at' => $viewedAt,
            ]);
        }
        $this->referral(['status' => 'accepted', 'viewed_at' => now()], $job);

        $this->actingAs($referrer)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Referrer/Index')
                ->has('referrals', 4)
                ->where('stats', [
                    'total' => 4,
                    'pending' => 2,
                    'accepted' => 1,
                    'rejected' => 1,
                    'viewed' => 3,
                    'not_viewed' => 1,
                    'view_rate' => 75,
                    'acceptance_rate' => 50,
                ]));
    }

    public function test_dashboard_stats_handle_no_referrals(): void
    {
        $this->actingAs($this->user('referrer'))
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 0)
                ->where('stats.view_rate', null)
                ->where('stats.acceptance_rate', null));
    }

    public function test_admin_dashboard_shows_platform_stats(): void
    {
        $popular = $this->job(['title' => 'Popular role']);
        $this->job(['title' => 'Draft role', 'status' => 'draft']);
        $topReferrer = $this->user('referrer', ['name' => 'Top Referrer']);
        $this->referral(['user_id' => $topReferrer->id, 'status' => 'accepted', 'viewed_at' => now(), 'responded_at' => now()], $popular);
        $this->referral(['user_id' => $topReferrer->id, 'status' => 'pending'], $popular);
        $this->referral(['status' => 'rejected', 'viewed_at' => now(), 'responded_at' => now()->subDay()]);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Index')
                ->where('stats.total', 3)
                ->where('stats.accepted', 1)
                ->where('stats.rejected', 1)
                ->where('stats.viewed', 2)
                ->where('stats.acceptance_rate', 50)
                ->where('jobStats', ['draft' => 1, 'active' => 2, 'archived' => 0])
                ->where('topJobs.0.title', 'Popular role')
                ->where('topJobs.0.referrals_count', 2)
                ->where('topJobs.0.accepted_referrals_count', 1)
                ->where('topReferrers.0.name', 'Top Referrer')
                ->has('recentResponses', 2)
                ->where('recentResponses.0.status', 'accepted'));
    }

    public function test_referrer_sees_in_app_notifications_and_can_mark_them_read(): void
    {
        $referral = $this->referral();
        $referrer = $referral->referrer;
        $this->post(route('referrals.invitation.respond', ['token' => $this->token($referral)]), ['decision' => 'accepted', 'resume_url' => 'https://example.com/resume.pdf']);

        $this->actingAs($referrer)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.unread_count', 1)
                ->where('notifications.items.0.status', 'accepted')
                ->where('notifications.items.0.candidate_note', null)
                ->where('notifications.items.0.message', "{$referral->candidate->name} accepted your referral for Senior Engineer"));

        $this->actingAs($referrer)
            ->post(route('notifications.read'))
            ->assertRedirect();

        $this->assertSame(0, $referrer->unreadNotifications()->count());
    }
}
