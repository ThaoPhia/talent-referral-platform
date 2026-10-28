<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Local sample data can only be seeded in the local environment.');
        }

        $faker = fake();
        $faker->seed(20261016);
        $faker->unique(true);

        $admin = User::query()->firstOrNew(['email' => 'sample.admin@example.test']);
        $admin->forceFill([
            'name' => 'Local Sample Admin',
            'password' => 'password',
            'email_verified_at' => now(),
            'type' => 'admin',
        ])->save();

        $referrers = collect(range(1, 15))
            ->map(fn (int $index): User => $this->saveSampleUser(
                $faker->name(),
                sprintf('sample.referrer.%02d@example.test', $index),
                'referrer',
                $index,
            ))
            ->all();

        $candidates = collect(range(1, 10))
            ->map(fn (int $index): User => $this->saveSampleUser(
                $faker->name(),
                sprintf('sample.candidate.%02d@example.test', $index),
                'candidate',
                $index + 15,
            ))
            ->all();

        $jobs = collect(range(1, 25))
            ->map(function (int $index) use ($faker): Job {
                $title = $faker->unique()->jobTitle();

                return Job::query()->updateOrCreate(
                    ['title' => "Sample: {$title}"],
                    [
                        'description' => "Join our team as a {$title} and help build thoughtful, reliable products. Collaborate across disciplines, share your expertise, and make a measurable impact.",
                        'location' => $faker->randomElement(['Remote', 'New York, NY', 'San Francisco, CA', 'Austin, TX', 'Toronto, ON']),
                        'post_on' => now()->subDays(25 - $index)->setTime(9, 0),
                        'closed_date' => now()->subDays(25 - $index)->addMonthsNoOverflow(3)->toDateString(),
                        'status' => $index > 22 ? 'archived' : 'active',
                    ],
                );
            })
            ->all();

        $statuses = ['pending', 'accepted', 'rejected'];

        for ($index = 0; $index < 15; $index++) {
            Referral::query()->updateOrCreate(
                [
                    'user_id' => $referrers[$index]->id,
                    'candidate_id' => $candidates[$index % count($candidates)]->id,
                    'job_id' => $jobs[$index]->id,
                ],
                ['status' => $statuses[$index % count($statuses)]],
            );
        }
    }

    private function saveSampleUser(string $name, string $email, string $type, int $index): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'email_verified_at' => now(),
            'type' => $type,
            'resume_url' => $type === 'candidate' ? "https://example.test/resumes/candidate-{$index}.pdf" : null,
            'note' => $type === 'candidate' ? 'Experienced candidate interested in collaborative teams.' : null,
        ])->save();

        return $user;
    }
}
