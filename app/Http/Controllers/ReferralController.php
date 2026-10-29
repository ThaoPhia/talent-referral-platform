<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferralRequest;
use App\Http\Requests\UpdateReferralRequest;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralInvitation;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        return Inertia::render('Dashboard/Admin/Referrals', [
            'referrals' => Referral::query()
                ->with(['referrer', 'candidate', 'job'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreReferralRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $referral = DB::transaction(function () use ($request, $user): Referral {
            $candidate = User::firstOrNew([
                'email' => $request->string('candidate_email')->toString(),
            ]);

            if (! $candidate->exists) {
                $candidate->password = Hash::make(Str::random(40));
                $candidate->type = 'candidate';
            }

            $candidate->forceFill([
                'name' => $request->string('candidate_name')->toString(),
            ])->save();

            return Referral::create([
                'user_id' => $user->id,
                'candidate_id' => $candidate->id,
                'job_id' => $request->integer('job_id'),
                'status' => 'pending',
                'referrer_note' => $request->filled('referrer_note') ? $request->string('referrer_note')->toString() : null,
            ]);
        });

        try {
            $referral->candidate->notify(new ReferralInvitation($referral));
        } catch (Throwable $exception) {
            // The referral is saved either way; don't turn a mail outage into a failed submission.
            report($exception);
            Inertia::flash('warn_toast', 'Referral created, but the invitation email could not be sent.');
        }

        return back()->with('success', 'Referral created successfully.');
    }

    public function update(UpdateReferralRequest $request, Referral $referral): RedirectResponse
    {
        $status = $request->string('status')->toString();

        $referral->update([
            'status' => $status,
            // Reopening a referral lets the candidate respond to the invitation again.
            'responded_at' => $status === 'pending' ? null : $referral->responded_at,
        ]);

        return redirect()->route('admin.referrals.index')->with('success', 'Referral updated successfully.');
    }
}
