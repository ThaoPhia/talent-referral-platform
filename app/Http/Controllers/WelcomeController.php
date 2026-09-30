<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Application;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Job;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'jobs' => Job::query()
                ->where('status', 'active')
                ->where('post_on', '<=', now())
                ->latest('post_on')
                ->get(['id', 'title', 'description', 'location', 'post_on']),
        ]);
    }
}
