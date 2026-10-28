<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'location', 'post_on', 'closed_on', 'status'])]
class Job extends Model
{
    public const STATUSES = ['draft', 'active', 'archived'];

    public const MAX_OPEN_MONTHS = 3;

    protected function casts(): array
    {
        return [
            'post_on' => 'immutable_datetime',
            'closed_on' => 'immutable_date:Y-m-d',
        ];
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}
