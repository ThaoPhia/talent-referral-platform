<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'location', 'post_on', 'status'])]
class Job extends Model
{
    protected function casts(): array
    {
        return [
            'post_on' => 'immutable_datetime',
        ];
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}
