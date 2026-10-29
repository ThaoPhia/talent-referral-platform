<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Support\ReferralStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $referrals = fn () => Referral::query()->where('user_id', $request->user()?->id);

        return Inertia::render('Dashboard/Referrer/Index', [
            'stats' => ReferralStats::for($referrals()),
            'referrals' => $referrals()
                ->with(['referrer', 'candidate', 'job'])
                ->latest()
                ->get(),
        ]);
    }
}
