<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralResponded extends Notification
{
    public function __construct(public Referral $referral)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Store the in-app alert first so it survives a mail outage.
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accepted = $this->referral->status === 'accepted';

        $mail = (new MailMessage())
            ->subject($this->message())
            ->greeting("Hi {$this->referral->referrer->name},")
            ->line($this->message() . '.');

        if (filled($this->referral->candidate_note)) {
            $mail->line("A note from {$this->referral->candidate->name}:")->line($this->referral->candidate_note);
        }

        return $mail
            ->lineIf($accepted, 'Our recruiting team will take it from here. Thanks for the referral!')
            ->lineIf(! $accepted, 'Thanks for the referral. We appreciate you thinking of us.')
            ->action('View your referrals', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'referral_id' => $this->referral->id,
            'job_id' => $this->referral->job_id,
            'status' => $this->referral->status,
            'message' => $this->message(),
            'candidate_note' => $this->referral->candidate_note,
        ];
    }

    private function message(): string
    {
        $verb = $this->referral->status === 'accepted' ? 'accepted' : 'declined';

        return "{$this->referral->candidate->name} {$verb} your referral for {$this->referral->job->title}";
    }
}
