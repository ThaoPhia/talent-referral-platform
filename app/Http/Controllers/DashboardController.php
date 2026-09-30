<?php

namespace App\Http\Controllers;

use App\Models\Referral;
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

        return Inertia::render('Dashboard', [
            'referrals' => Referral::query()
                ->with(['user', 'referrer', 'job'])
                ->where('user_id', $request->user()?->id)
                ->latest()
                ->get(),
        ]);
    }
}
