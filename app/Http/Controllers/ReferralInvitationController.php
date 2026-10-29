<?php

namespace App\Http\Controllers;

use App\Http\Requests\RespondToReferralRequest;
use App\Models\Referral;
use App\Notifications\ReferralResponded;
use App\Support\ReferralInvitationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        // Find the referral associated with the given token. If not found, an exception will be thrown.
        $referral = ReferralInvitationToken::findReferral($token);

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

    public function respond(RespondToReferralRequest $request, string $token): RedirectResponse
    {
        // Find the referral associated with the given token. If not found, an exception will be thrown.
        $referral = $request->referral();
        $accepting = $request->accepting();
        $validated = $request->validated();

        $resume = null;

        $file = $accepting ? $request->file('resume_file') : null;

        try {
            $updated = DB::transaction(function () use ($referral, $validated, $accepting, $file, &$resume): int {
                $updated = Referral::query()->whereKey($referral->id)->where('status', 'pending')->update([
                    'status' => $validated['decision'],
                    'candidate_note' => $validated['candidate_note'] ?? null,
                    'candidate_resume' => $accepting && ! $file instanceof UploadedFile ? $validated['resume_url'] : null,
                    'responded_at' => now(),
                    'viewed_at' => DB::raw('COALESCE(viewed_at, CURRENT_TIMESTAMP)'),
                ]);

                // If the referral was successfully updated and a resume file was uploaded, store the file and update the referral with its path.
                if ($updated > 0 && $file instanceof UploadedFile) {
                    $resume = $file->store((string) $referral->id, Referral::RESUME_DISK);
                    Referral::query()->whereKey($referral->id)->update(['candidate_resume' => $resume]);
                }

                return $updated;
            });
        } catch (Throwable $exception) {
            if (is_string($resume)) {
                Storage::disk(Referral::RESUME_DISK)->delete($resume);
            }

            throw $exception;
        }

        if ($updated === 0) {
            Inertia::flash('warn_toast', 'You have already responded to this referral.');

            return to_route('referrals.invitation.show', ['token' => $token]);
        }

        $referral->refresh();

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

}
