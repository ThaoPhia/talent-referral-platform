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
                ->has('jobs.data', 1)
                ->where('jobs.data.0.id', $publishedJob->id));
    }

    public function test_homepage_paginates_jobs_ten_per_page_by_default(): void
    {
        foreach (range(1, 12) as $i) {
            Job::create([
                'title' => "Job {$i}",
                'description' => 'Visible job.',
                'location' => 'Remote',
                'post_on' => now()->subDays($i),
            ]);
        }

        $this->get(route('welcome'))
            ->assertInertia(fn($page) => $page
                ->has('jobs.data', 10)
                ->where('jobs.per_page', 10)
                ->where('jobs.total', 12));

        $this->get(route('welcome', ['page' => 2]))
            ->assertInertia(fn($page) => $page
                ->has('jobs.data', 2)
                ->where('jobs.current_page', 2));

        $this->get(route('welcome', ['per_page' => 20]))
            ->assertInertia(fn($page) => $page->has('jobs.data', 12));

        $this->get(route('welcome', ['per_page' => 50]))
            ->assertInertia(fn($page) => $page->where('jobs.per_page', 10));
    }

    public function test_homepage_filters_jobs_by_search(): void
    {
        $match = Job::create([
            'title' => 'Senior Laravel Developer',
            'description' => 'Build things.',
            'location' => 'Remote',
            'post_on' => now()->subDay(),
        ]);

        Job::create([
            'title' => 'Designer',
            'description' => 'Design things.',
            'location' => 'Austin',
            'post_on' => now()->subDay(),
        ]);

        $this->get(route('welcome', ['search' => 'laravel']))
            ->assertInertia(fn($page) => $page
                ->where('search', 'laravel')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.id', $match->id));
    }

    public function test_homepage_search_treats_wildcards_literally(): void
    {
        Job::create([
            'title' => 'Designer',
            'description' => 'Design things.',
            'location' => 'Austin',
            'post_on' => now()->subDay(),
        ]);

        $this->get(route('welcome', ['search' => '%']))
            ->assertInertia(fn($page) => $page->has('jobs.data', 0));

        $this->get(route('welcome', ['search' => 'Des_gner']))
            ->assertInertia(fn($page) => $page->has('jobs.data', 0));
    }
}
