<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isReferrer();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'candidate_name' => ['required', 'string', 'max:255'],
            'candidate_email' => ['required', 'email', 'max:255'],
            'referrer_note' => ['nullable', 'string', 'max:5000'],
            'job_id' => [
                'required',
                'integer',
                // Only published, still-open jobs can be referred to; drafts and future posts stay hidden.
                Rule::exists(Job::class, 'id')->where(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->where('post_on', '<=', now())
                    ->whereDate('closed_on', '>=', today(config('app.schedule_timezone'))->toDateString())),
            ],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['candidate_email', 'job_id'])) {
                    return;
                }

                $alreadyReferred = Referral::query()
                    ->where('job_id', $this->integer('job_id'))
                    ->whereHas('candidate', fn ($query) => $query->where('email', $this->string('candidate_email')->toString()))
                    ->exists();

                if ($alreadyReferred) {
                    $validator->errors()->add('candidate_email', 'This candidate has already been referred to this job.');
                }
            },
        ];
    }
}
