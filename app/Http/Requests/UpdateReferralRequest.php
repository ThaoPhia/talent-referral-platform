<?php

namespace App\Http\Requests;

use App\Models\Referral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        $referral = $this->route('referral');

        return $referral instanceof Referral && Gate::allows('update', $referral);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'viewed', 'accepted', 'rejected'])],
        ];
    }
}
