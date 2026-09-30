<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return Inertia::render('Admin/Jobs', [
            'jobs' => Job::query()->latest('post_on')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return Inertia::render('Admin/Jobs/Create');
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        Job::create($request->validated());

        return redirect()->route('admin.jobs.index')->with('success', 'Job created successfully.');
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $job->update($request->validated());

        return redirect()->route('admin.jobs.index')->with('success', 'Job updated successfully.');
    }

    public function edit(Request $request, Job $job): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return Inertia::render('Admin/Jobs/Edit', [
            'job' => $job,
        ]);
    }
}
