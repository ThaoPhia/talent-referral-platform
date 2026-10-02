<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ReferrerApplicationDenied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminReferrerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        return Inertia::render('Admin/Referrers', [
            'referrers' => User::query()->where('type', 'referrer')
                ->where('status', 'pending')->latest()->get(['id', 'name', 'email', 'status', 'created_at']),
        ]);
    }

    public function show(Request $request, User $referrer): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($referrer->isReferrer(), 404);

        return Inertia::render('Admin/Referrer', [
            'referrer' => $referrer->only('id', 'name', 'email', 'status', 'created_at'),
        ]);
    }

    public function approve(Request $request, User $referrer): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($referrer->isReferrer() && $referrer->status === 'pending', 409);

        $referrer->forceFill(['status' => 'active'])->save();

        return redirect()->route('admin.referrers.show', $referrer)->with('success', 'Referrer approved.');
    }

    public function deny(Request $request, User $referrer): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($referrer->isReferrer() && $referrer->status === 'pending', 409);

        $referrer->forceFill(['status' => 'denied'])->save();
        $referrer->notify(new ReferrerApplicationDenied);

        return redirect()->route('admin.referrers.show', $referrer)->with('success', 'Referrer denied.');
    }
}
