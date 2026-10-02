<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferralRequest;
use App\Http\Requests\UpdateReferralRequest;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\CandidateJobReferral;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Referral::class);

        return Inertia::render('Admin/Referrals', [
            'referrals' => Referral::query()
                ->with(['recruiter', 'candidate', 'job'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        $user = $request->user();

        [$candidate, $referral] = DB::transaction(function () use ($request, $user): array {
            $candidate = User::firstOrNew([
                'email' => $request->string('candidate_email')->toString(),
            ]);

            if (! $candidate->exists) {
                $candidate->password = Hash::make(Str::random(40));
                $candidate->type = 'normal';
            }

            $candidate->forceFill([
                'name' => $request->string('candidate_name')->toString(),
                'resume_url' => $request->string('resume_url')->toString(),
                'note' => $request->string('note')->toString(),
            ])->save();

            $referral = Referral::create([
                'user_id' => $user->id, // Referred by this user
                'referrer_id' => $candidate->id, // The referred candidate
                'job_id' => $request->integer('job_id'),
                'status' => 'pending',
            ]);

            return [$candidate, $referral];
        });

        $candidate->notify(new CandidateJobReferral($referral->load('job')));

        return back()->with('success', 'Referral created successfully.');
    }

    public function update(UpdateReferralRequest $request, Referral $referral): RedirectResponse
    {
        $referral->update($request->validated());

        return redirect()->route('admin.referrals.index')->with('success', 'Referral updated successfully.');
    }
}
