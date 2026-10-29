<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Notifications\ReferralResponded;
use App\Support\ReferralInvitationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Public pages that candidates reach from the encrypted link in their invitation email.
 */
class ReferralInvitationController extends Controller
{
    public function show(string $token): Response
    {
        $referral = $this->findReferral($token);

        if ($referral->viewed_at === null) {
            $referral->forceFill(['viewed_at' => now()])->save();
        }

        $job = $referral->job;

        return Inertia::render('Referrals/Invitation', [
            'token' => $token,
            'referral' => [
                'status' => $referral->status,
                'responded_at' => $referral->responded_at,
                'can_respond' => $referral->canBeRespondedTo(),
                'referrer_name' => $referral->referrer->name,
                'referrer_note' => $referral->referrer_note,
                'candidate_note' => $referral->candidate_note,
                'resume_type' => $referral->resume_type,
                'candidate_name' => $referral->candidate->name,
            ],
            'job' => [
                'title' => $job->title,
                // Don't reveal details of a job that was moved back to draft.
                'description' => $job->status === 'draft' ? null : $job->description,
                'location' => $job->location,
                'post_on' => $job->post_on,
                'closed_on' => $job->closed_on,
            ],
        ]);
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $referral = $this->findReferral($token);

        if (! $referral->canBeRespondedTo()) {
            Inertia::flash('warn_toast', $referral->status === 'pending'
                ? 'This job is no longer accepting responses.'
                : 'You have already responded to this referral.');

            return to_route('referrals.invitation.show', ['token' => $token]);
        }

        $accepting = $request->input('decision') === 'accepted';

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['accepted', 'rejected'])],
            'candidate_note' => ['nullable', 'string', 'max:5000'],
            'resume_url' => [
                Rule::requiredIf($accepting && ! $request->hasFile('resume_file')),
                'nullable',
                'url:http,https',
                'max:2048',
                'prohibits:resume_file',
            ],
            'resume_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:2048'],
        ], [
            'resume_url.required' => 'Add a link to your resume or upload a file to accept this referral.',
            'resume_url.prohibits' => 'Provide either a resume link or a file, not both.',
            'resume_file.max' => 'The resume file may not be larger than 2 MB.',
        ]);

        $resume = null;

        if ($accepting) {
            $file = $request->file('resume_file');
            $resume = $file instanceof UploadedFile
                ? $file->store((string) $referral->id, Referral::RESUME_DISK)
                : $validated['resume_url'];
        }

        $referral->forceFill([
            'status' => $validated['decision'],
            'candidate_note' => $validated['candidate_note'] ?? null,
            'candidate_resume' => $resume,
            'responded_at' => now(),
            'viewed_at' => $referral->viewed_at ?? now(),
        ])->save();

        try {
            $referral->referrer->notify(new ReferralResponded($referral));
        } catch (Throwable $exception) {
            // The candidate's answer is saved; a mail outage shouldn't show them an error.
            report($exception);
        }

        Inertia::flash('success_toast', $validated['decision'] === 'accepted'
            ? 'Thanks! Your referral has been accepted.'
            : 'Thanks for letting us know. The referral has been declined.');

        return to_route('referrals.invitation.show', ['token' => $token]);
    }

    private function findReferral(string $token): Referral
    {
        $pair = ReferralInvitationToken::decode($token);

        abort_if($pair === null, 404);

        return Referral::query()
            ->with(['job', 'referrer', 'candidate'])
            ->where('job_id', $pair['job_id'])
            ->where('candidate_id', $pair['candidate_id'])
            ->firstOrFail();
    }
}
