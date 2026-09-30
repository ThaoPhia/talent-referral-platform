<?php

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_only_published_active_jobs(): void
    {
        $publishedJob = Job::create([
            'title' => 'Published job',
            'description' => 'This job is visible.',
            'location' => 'Remote',
            'post_on' => now()->subDay(),
        ]);

        Job::create([
            'title' => 'Future job',
            'description' => 'This job is not visible yet.',
            'location' => 'Remote',
            'post_on' => now()->addDay(),
        ]);

        Job::create([
            'title' => 'Archived job',
            'description' => 'This job is no longer visible.',
            'location' => 'Remote',
            'post_on' => now()->subDay(),
            'status' => 'archived',
        ]);

        $this->get(route('welcome'))
            ->assertInertia(fn($page) => $page
                ->component('Welcome')
                ->has('jobs', 1)
                ->where('jobs.0.id', $publishedJob->id));
    }
}
