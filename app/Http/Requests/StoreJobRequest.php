<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\User;
use App\Rules\ClosedDateWithinRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'post_on' => ['required', 'date'],
            'closed_date' => ['required', 'date', new ClosedDateWithinRange($this->input('post_on'))],
            'status' => ['sometimes', Rule::in(Job::STATUSES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'post_on' => 'post date',
            'closed_date' => 'close date',
        ];
    }
}
