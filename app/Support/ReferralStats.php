<?php

namespace App\Support;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Builder;

class ReferralStats
{
    /**
     * @param  Builder<Referral>  $referrals
     * @return array{total: int, pending: int, accepted: int, rejected: int, viewed: int, not_viewed: int, view_rate: int|null, acceptance_rate: int|null}
     */
    public static function for(Builder $referrals): array
    {
        $row = $referrals->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'pending' then 1 else 0 end) as pending")
            ->selectRaw("sum(case when status = 'accepted' then 1 else 0 end) as accepted")
            ->selectRaw("sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            ->selectRaw('sum(case when viewed_at is not null then 1 else 0 end) as viewed')
            ->first();

        $total = (int) ($row->total ?? 0);
        $accepted = (int) ($row->accepted ?? 0);
        $rejected = (int) ($row->rejected ?? 0);
        $viewed = (int) ($row->viewed ?? 0);

        return [
            'total' => $total,
            'pending' => (int) ($row->pending ?? 0),
            'accepted' => $accepted,
            'rejected' => $rejected,
            'viewed' => $viewed,
            'not_viewed' => $total - $viewed,
            'view_rate' => self::percent($viewed, $total),
            // Share of decided referrals that were accepted.
            'acceptance_rate' => self::percent($accepted, $accepted + $rejected),
        ];
    }

    private static function percent(int $part, int $whole): ?int
    {
        return $whole === 0 ? null : (int) round($part / $whole * 100);
    }
}
