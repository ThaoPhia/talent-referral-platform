<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use App\Support\ReferralStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        $jobCounts = Job::query()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Dashboard/Admin/Index', [
            'stats' => ReferralStats::for(Referral::query()),
            'jobStats' => collect(Job::STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => (int) ($jobCounts[$status] ?? 0)])
                ->all(),
            'topJobs' => Job::query()
                ->select(['id', 'title', 'status'])
                ->withCount([
                    'referrals',
                    'referrals as accepted_referrals_count' => fn ($query) => $query->where('status', 'accepted'),
                ])
                ->has('referrals')
                ->orderByDesc('referrals_count')
                ->orderBy('title')
                ->limit(5)
                ->get(),
            'topReferrers' => User::query()
                ->select(['id', 'name', 'email'])
                ->withCount([
                    'referrals',
                    'referrals as accepted_referrals_count' => fn ($query) => $query->where('status', 'accepted'),
                ])
                ->has('referrals')
                ->orderByDesc('accepted_referrals_count')
                ->orderByDesc('referrals_count')
                ->orderBy('name')
                ->limit(5)
                ->get(),
            'recentResponses' => Referral::query()
                ->with(['referrer:id,name', 'candidate:id,name', 'job:id,title'])
                ->whereNotNull('responded_at')
                ->latest('responded_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
