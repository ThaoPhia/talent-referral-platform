<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessage extends Notification
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $message,
    ) {
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
        return (new MailMessage())
            ->subject('New website contact message')
            ->replyTo($this->email)
            ->greeting('New contact message')
            ->line('From: '.$this->name.' ('.$this->email.')')
            ->line($this->message);
    }
}
