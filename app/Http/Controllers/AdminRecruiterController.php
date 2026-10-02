<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ReferrerApplicationDenied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRecruiterController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        return Inertia::render('Admin/Recruiters', [
            'recruiters' => User::query()->where('type', 'recruiter')
                ->where('status', 'pending')->latest()->get(['id', 'name', 'email', 'status', 'created_at']),
        ]);
    }

    public function show(Request $request, User $recruiter): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($recruiter->isRecruiter(), 404);

        return Inertia::render('Admin/Recruiter', [
            'recruiter' => $recruiter->only('id', 'name', 'email', 'status', 'created_at'),
        ]);
    }

    public function approve(Request $request, User $recruiter): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($recruiter->isRecruiter() && $recruiter->status === 'pending', 409);

        $recruiter->forceFill(['status' => 'active'])->save();

        return redirect()->route('admin.recruiters.show', $recruiter)->with('success', 'Recruiter approved.');
    }

    public function deny(Request $request, User $recruiter): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless($recruiter->isRecruiter() && $recruiter->status === 'pending', 409);

        $recruiter->forceFill(['status' => 'denied'])->save();
        $recruiter->notify(new ReferrerApplicationDenied);

        return redirect()->route('admin.recruiters.show', $recruiter)->with('success', 'Recruiter denied.');
    }
}
