<?php

namespace App\Http\Controllers;

use App\Notifications\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact', [
            'email' => config('mail.from.address'),
            'address' => config('contact.address'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Notification::route('mail', config('mail.from.address'))
                ->notify(new ContactMessage($validated['name'], $validated['email'], $validated['message']));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'message' => 'Your message could not be sent. Please try again later or email us directly.',
            ]);
        }

        Inertia::flash('success_toast', 'Thank you! Your message has been sent.');

        return to_route('contact');
    }
}
