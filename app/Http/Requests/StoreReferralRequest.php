<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'resume_url' => ['required', 'string', 'max:2048'],
            'note' => ['required', 'string', 'max:5000'],
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
}
