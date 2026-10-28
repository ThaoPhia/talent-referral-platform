<?php

namespace App\Rules;

use App\Models\Job;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Throwable;

class ClosedDateWithinRange implements ValidationRule
{
    public function __construct(private readonly mixed $postOn)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = $this->errorFor($value);

        if ($message !== null) {
            $fail($message);
        }
    }

    public function errorFor(mixed $value): ?string
    {
        if (! $this->isDateLike($this->postOn) || ! $this->isDateLike($value)) {
            return null;
        }

        try {
            $postOn = Carbon::parse($this->postOn)->startOfDay();
            $closedDate = Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }

        if ($closedDate->lt($postOn)) {
            return 'The close date must be on or after the post date.';
        }

        $maxDate = $postOn->copy()->addMonthsNoOverflow(Job::MAX_OPEN_MONTHS);

        if ($closedDate->gt($maxDate)) {
            return sprintf(
                'The close date must be no more than %d months after the post date (%s).',
                Job::MAX_OPEN_MONTHS,
                $maxDate->toDateString(),
            );
        }

        return null;
    }

    private function isDateLike(mixed $value): bool
    {
        return $value instanceof DateTimeInterface || (is_string($value) && $value !== '');
    }
}
