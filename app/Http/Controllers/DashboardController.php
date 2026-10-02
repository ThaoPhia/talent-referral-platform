<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (Gate::allows('viewAdminDashboard', User::class)) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Dashboard', [
            'referrals' => Referral::query()
                ->with(['recruiter', 'candidate', 'job'])
                ->where('user_id', $request->user()?->id)
                ->latest()
                ->get(),
        ]);
    }
}
