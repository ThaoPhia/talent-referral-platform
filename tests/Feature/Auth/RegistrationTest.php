<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use App\Mail\ReferrerSignupAlert;
use App\Notifications\ReferrerSignupReceived;
use App\Notifications\ReferrerApplicationDenied;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();
        Mail::fake();
        $admin = User::where('type', 'admin')->firstOrFail();
        $secondAdmin = User::factory()->create();
        $secondAdmin->forceFill(['type' => 'admin', 'status' => 'active'])->save();
        $inactiveAdmin = User::factory()->create();
        $inactiveAdmin->forceFill(['type' => 'admin', 'status' => 'inactive'])->save();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'type' => 'referrer',
            'status' => 'pending',
        ]);
        $this->assertSame('pending', User::where('email', 'test@example.com')->firstOrFail()->status);

        $referrer = User::where('email', 'test@example.com')->firstOrFail();
        Notification::assertSentTo($referrer, ReferrerSignupReceived::class);
        Mail::assertSent(
            ReferrerSignupAlert::class,
            fn(ReferrerSignupAlert $mail) =>
            $mail->hasTo($admin->email)
                && $mail->hasBcc($secondAdmin->email)
                && ! $mail->hasBcc($inactiveAdmin->email)
                && str_contains($mail->render(), route('admin.referrers.show', $referrer))
        );
        Mail::assertSentCount(1);
        Notification::assertNotSentTo($referrer, VerifyEmail::class);
        Notification::assertCount(1);
    }

    public function test_registration_sends_one_alert_with_only_one_active_admin(): void
    {
        Notification::fake();
        Mail::fake();
        $admin = User::where('type', 'admin')->firstOrFail();

        $this->post('/register', [
            'name' => 'Solo Applicant',
            'email' => 'solo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        Mail::assertSent(
            ReferrerSignupAlert::class,
            fn(ReferrerSignupAlert $mail) =>
            $mail->hasTo($admin->email) && $mail->bcc === []
        );
        Mail::assertSentCount(1);
    }

    public function test_only_admins_can_review_and_approve_a_pending_referrer(): void
    {
        Notification::fake();
        $admin = User::where('type', 'admin')->firstOrFail();
        $referrer = User::factory()->create();

        $this->actingAs($referrer)->get(route('admin.referrers.show', $referrer))->assertForbidden();
        $this->actingAs($referrer)->post(route('admin.referrers.approve', $referrer))->assertForbidden();

        $referrer->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($admin)->get(route('admin.referrers.index'))->assertOk();
        $this->get(route('admin.referrers.show', $referrer))
            ->assertInertia(fn($page) => $page->component('Admin/Referrer')
                ->where('referrer.email', $referrer->email)
                ->where('referrer.status', 'pending'));

        $this->post(route('admin.referrers.approve', $referrer))->assertRedirect();
        $this->assertSame('active', $referrer->fresh()->status);
        Notification::assertSentTo($referrer, VerifyEmail::class, 1);
        $this->post(route('admin.referrers.deny', $referrer))->assertStatus(409);
    }

    public function test_only_pending_referrers_can_be_denied(): void
    {
        Notification::fake();
        $admin = User::where('type', 'admin')->firstOrFail();
        $referrer = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.referrers.deny', $referrer))->assertRedirect();
        $this->assertSame('denied', $referrer->fresh()->status);
        Notification::assertSentTo($referrer, ReferrerApplicationDenied::class);
        $this->post(route('admin.referrers.approve', $referrer))->assertStatus(409);
    }

    public function test_verification_follows_activation_from_any_status_change(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        event(new Registered($user));
        Notification::assertNotSentTo($user, VerifyEmail::class);

        $user->forceFill(['status' => 'active'])->save();
        Notification::assertSentTo($user, VerifyEmail::class, 1);

        $user->forceFill(['name' => 'Updated Name'])->save();
        Notification::assertSentTo($user, VerifyEmail::class, 1);
    }
}
