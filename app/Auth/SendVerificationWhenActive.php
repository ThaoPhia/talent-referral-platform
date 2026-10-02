<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

class SendVerificationWhenActive extends SendEmailVerificationNotification
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User || $event->user->status === 'active') {
            parent::handle($event);
        }
    }

    public function onStatusUpdated(User $user): void
    {
        if ($user->wasChanged('status') && $user->status === 'active' && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}