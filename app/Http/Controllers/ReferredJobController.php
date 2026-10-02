<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferredJobController extends Controller
{
    public function show(Request $request, User $user, Referral $referral): Response
    {
        abort_unless($referral->referrer_id === $user->id, 404);
        abort_unless(in_array($referral->status, ['pending', 'viewed', 'accepted'], true), 403);

        $job = $referral->job;
        if (! $job instanceof Job) {
            abort(404);
        }

        if ($referral->status === 'pending') {
            $referral->update(['status' => 'viewed']);
        }

        return Inertia::render('ReferredJob', [
            'job' => [
                'title' => $job->title,
                'location' => $job->location,
                'post_on' => $job->post_on,
                'description' => $job->description,
            ],
            'accepted' => $referral->status === 'accepted',
            'acceptUrl' => $request->fullUrl(),
        ]);
    }

    public function accept(Request $request, User $user, Referral $referral): RedirectResponse
    {
        abort_unless($referral->referrer_id === $user->id, 404);
        abort_unless(in_array($referral->status, ['viewed', 'accepted'], true), 403);

        if ($referral->status === 'viewed') {
            $referral->update(['status' => 'accepted']);
        }

        return redirect()->to($request->fullUrl());
    }
}
