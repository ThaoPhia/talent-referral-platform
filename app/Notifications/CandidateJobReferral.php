<?php

namespace App\Notifications;

use App\Models\Job;
use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use LogicException;

class CandidateJobReferral extends Notification
{
    use Queueable;

    public function __construct(public Referral $referral) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->referral->job;
        if (! $job instanceof Job) {
            throw new LogicException('Cannot send a referral for a missing job.');
        }

        return (new MailMessage)
            ->subject('You were referred for ' . $job->title)
            ->line('A recruiter referred you for ' . $job->title . '.')
            ->action('View job', URL::temporarySignedRoute('referred-jobs.show', now()->addDays(7), [
                'user' => $this->referral->referrer_id,
                'referral' => $this->referral->id,
            ]))
            ->line('This link expires in 7 days.');
    }
}
