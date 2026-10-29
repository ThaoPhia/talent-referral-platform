<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralResumeController extends Controller
{
    public function __invoke(Referral $referral, #[CurrentUser] User $user): RedirectResponse|StreamedResponse
    {
        abort_unless($user->isAdmin() || $referral->user_id === $user->id, 403);

        if ($referral->hasResumeLink()) {
            return redirect()->away((string) $referral->candidate_resume);
        }

        $disk = Storage::disk(Referral::RESUME_DISK);
        $path = (string) $referral->candidate_resume;

        abort_unless($referral->hasUploadedResume() && $disk->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $disk->download($path, Str::slug($referral->candidate->name . ' resume') . ($extension !== '' ? ".{$extension}" : ''));
    }
}
