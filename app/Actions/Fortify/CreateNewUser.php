<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Mail\ReferrerSignupAlert;
use App\Notifications\ReferrerSignupReceived;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ])->validate();

        $user = User::forceCreate([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'type' => 'recruiter',
            'status' => 'pending',
        ]);

        $user->notify(new ReferrerSignupReceived);
        $adminEmails = User::query()->where('type', 'admin')->where('status', 'active')->pluck('email');
        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails->first())
                ->bcc($adminEmails->slice(1)->all())
                ->send(new ReferrerSignupAlert($user));
        }

        return $user;
    }
}
