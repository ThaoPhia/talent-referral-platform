<?php

namespace Tests\Feature;

use Database\Seeders\LocalSampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class LocalSampleDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeder_creates_sample_data_and_is_safe_to_rerun(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $seeder = app(LocalSampleDataSeeder::class);
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('jobs', 25);
        $this->assertDatabaseCount('referrals', 15);
        $this->assertDatabaseCount('users', 28);
        $this->assertDatabaseHas('users', [
            'email' => 'sample.admin@example.test',
            'type' => 'admin',
        ]);
        $this->assertSame(25, DB::table('jobs')->where('title', 'like', 'Sample: %')->count());
        $this->assertDatabaseHas('referrals', [
            'status' => 'pending',
        ]);
    }

    public function test_local_seeder_refuses_to_run_outside_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local sample data can only be seeded in the local environment.');

        app(LocalSampleDataSeeder::class)->run();
    }
}
