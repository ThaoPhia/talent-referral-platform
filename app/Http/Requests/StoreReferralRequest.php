<?php

namespace App\Http\Requests;

use App\Models\Referral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Referral::class);
    }

    public function rules(): array
    {
        return [
            'candidate_name' => ['required', 'string', 'max:255'],
            'candidate_email' => ['required', 'email', 'max:255'],
            'resume_url' => ['required', 'string', 'max:2048'],
            'note' => ['required', 'string', 'max:5000'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ];
    }
}
