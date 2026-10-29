<?php

namespace App\Http\Requests;

use App\Models\Referral;
use App\Support\ReferralInvitationToken;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RespondToReferralRequest extends FormRequest
{
    private Referral $referral;

    public function authorize(): bool
    {
        $token = $this->route('token');
        abort_unless(is_string($token), 404);
        $this->referral = ReferralInvitationToken::findReferral($token);

        if (! $this->referral->canBeRespondedTo()) {
            Inertia::flash('warn_toast', $this->referral->status === 'pending'
                ? 'This job is no longer accepting responses.'
                : 'You have already responded to this referral.');

            throw new HttpResponseException(to_route('referrals.invitation.show', ['token' => $token]));
        }

        return true;
    }

    public function referral(): Referral
    {
        return $this->referral;
    }

    public function accepting(): bool
    {
        return $this->input('decision') === 'accepted';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['accepted', 'rejected'])],
            'candidate_note' => ['nullable', 'string', 'max:5000'],
            'resume_url' => [
                Rule::requiredIf($this->accepting() && ! $this->hasFile('resume_file')),
                'nullable',
                'url:http,https',
                'max:2048',
                'prohibits:resume_file',
            ],
            'resume_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume_url.required' => 'Add a link to your resume or upload a file to accept this referral.',
            'resume_url.prohibits' => 'Provide either a resume link or a file, not both.',
            'resume_file.max' => 'The resume file may not be larger than 2 MB.',
        ];
    }
}
