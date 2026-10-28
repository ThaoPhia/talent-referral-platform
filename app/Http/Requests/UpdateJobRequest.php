<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\User;
use App\Rules\ClosedOnWithinRange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'post_on' => ['sometimes', 'required', 'date'],
            'closed_on' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', Rule::in(Job::STATUSES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'post_on' => 'post date',
            'closed_on' => 'close date',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['post_on', 'closed_on']) || $validator->errors()->hasAny(['post_on', 'closed_on'])) {
                    return;
                }

                /** @var Job $job */
                $job = $this->route('job');

                $message = (new ClosedOnWithinRange($this->input('post_on', $job->post_on)))
                    ->errorFor($this->input('closed_on', $job->closed_on));

                if ($message !== null) {
                    $validator->errors()->add('closed_on', $message);
                }
            },
        ];
    }
}
