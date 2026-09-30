<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isMember();
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
