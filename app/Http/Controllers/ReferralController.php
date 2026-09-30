<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferralRequest;
use App\Http\Requests\UpdateReferralRequest;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return Inertia::render('Admin/Referrals', [
            'referrals' => Referral::query()
                ->with(['user', 'referrer', 'job'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
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

            Referral::create([
                'user_id' => $user->id, // Referred by this user
                'referrer_id' => $candidate->id, // The referred candidate
                'job_id' => $request->integer('job_id'),
                'status' => 'pending',
            ]);
        });

        return back()->with('success', 'Referral created successfully.');
    }

    public function update(UpdateReferralRequest $request, Referral $referral): RedirectResponse
    {
        $referral->update($request->validated());

        return redirect()->route('admin.referrals.index')->with('success', 'Referral updated successfully.');
    }
}
