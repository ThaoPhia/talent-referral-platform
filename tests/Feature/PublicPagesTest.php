<?php

namespace Tests\Feature;

use App\Notifications\ContactMessage;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_about_page_is_public(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('About'));
    }

    public function test_contact_page_uses_configured_site_email_and_address(): void
    {
        config([
            'mail.from.address' => 'team@example.test',
            'contact.address' => '456 New Street',
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contact')
                ->where('email', 'team@example.test')
                ->where('address', '456 New Street'));
    }

    public function test_contact_form_emails_site_inbox_with_visitor_reply_to(): void
    {
        Notification::fake();
        config(['mail.from.address' => 'team@example.test']);

        $this->post(route('contact.store'), [
            'name' => 'Alex Rivera',
            'email' => 'alex@example.test',
            'message' => 'I have a question about referrals.',
        ])->assertRedirect(route('contact'))->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            ContactMessage::class,
            function (ContactMessage $notification, array $channels, AnonymousNotifiable $notifiable): bool {
                $mail = $notification->toMail($notifiable);

                return $notifiable->routes['mail'] === 'team@example.test'
                    && $channels === ['mail']
                    && $notification->name === 'Alex Rivera'
                    && $notification->email === 'alex@example.test'
                    && $notification->message === 'I have a question about referrals.'
                    && $mail->replyTo === [['alex@example.test', null]]
                    && in_array($notification->message, $mail->introLines, true);
            },
        );
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Notification::assertNothingSent();
    }

    public function test_contact_form_rejects_invalid_email_and_oversized_input(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => str_repeat('a', 256),
            'email' => 'not-an-email',
            'message' => str_repeat('a', 5001),
        ])->assertSessionHasErrors(['name', 'email', 'message']);

        Notification::assertNothingSent();
    }

    public function test_contact_form_reports_mail_delivery_failure(): void
    {
        $this->mock(MailChannel::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        });

        $this->from(route('contact'))->post(route('contact.store'), [
            'name' => 'Alex',
            'email' => 'alex@example.test',
            'message' => 'A question.',
        ])->assertRedirect(route('contact'))
            ->assertSessionHasErrors([
                'message' => 'Your message could not be sent. Please try again later or email us directly.',
            ]);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Notification::fake();

        $data = ['name' => 'Alex', 'email' => 'alex@example.test', 'message' => 'Hello.'];
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $data)->assertRedirect(route('contact'));
        }

        $this->post(route('contact.store'), $data)->assertStatus(429);
        Notification::assertSentOnDemandTimes(ContactMessage::class, 5);
    }
}
