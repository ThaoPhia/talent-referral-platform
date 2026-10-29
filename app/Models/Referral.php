<?php

namespace App\Models;

use App\Support\ReferralInvitationToken;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The foreign keys are required columns, so these relations are never null.
 *
 * @property-read User $referrer
 * @property-read User $candidate
 * @property-read Job $job
 */
#[Hidden(['candidate_resume'])]
#[Fillable(['user_id', 'candidate_id', 'job_id', 'status', 'referrer_note', 'candidate_note', 'candidate_resume', 'viewed_at', 'responded_at'])]
class Referral extends Model
{
    public const STATUSES = ['pending', 'accepted', 'rejected'];

    public const RESUME_DISK = 'resumes';

    /**
     * The raw resume value stays hidden; the frontend gets a safe link instead.
     *
     * @var list<string>
     */
    protected $appends = ['resume_type', 'resume_link'];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function invitationUrl(): string
    {
        return route('referrals.invitation.show', ['token' => ReferralInvitationToken::encode($this)]);
    }

    public function hasResumeLink(): bool
    {
        return is_string($this->candidate_resume)
            && preg_match('#^https?://#i', $this->candidate_resume) === 1;
    }

    public function hasUploadedResume(): bool
    {
        return filled($this->candidate_resume) && ! $this->hasResumeLink();
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function resumeType(): Attribute
    {
        return Attribute::get(fn (): ?string => match (true) {
            $this->hasResumeLink() => 'link',
            $this->hasUploadedResume() => 'file',
            default => null,
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function resumeLink(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->candidate_resume) && $this->exists
            ? route('referrals.resume', $this)
            : null);
    }

    public function canBeRespondedTo(): bool
    {
        return $this->status === 'pending' && $this->job->isOpenForReferrals();
    }
}
