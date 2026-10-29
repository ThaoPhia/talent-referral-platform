<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralInvitation extends Notification
{
    public function __construct(public Referral $referral)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $referrer = $this->referral->referrer->name;
        $job = $this->referral->job;

        $mail = (new MailMessage())
            ->subject("{$referrer} referred you for {$job->title}")
            ->greeting("Hi {$this->referral->candidate->name},")
            ->line("{$referrer} thinks you'd be a great fit for the {$job->title} role ({$job->location}).");

        if (filled($this->referral->referrer_note)) {
            $mail->line("A note from {$referrer}:")->line($this->referral->referrer_note);
        }

        return $mail
            ->line('Review the job and let us know whether you would like to be considered.')
            ->action('View the job', $this->referral->invitationUrl())
            ->line("This role accepts responses until {$job->closed_on->format('F j, Y')}.");
    }
}
