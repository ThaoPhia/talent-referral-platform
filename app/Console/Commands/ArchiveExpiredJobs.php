<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:archive-expired')]
#[Description('Archive active jobs whose close date has been reached')]
class ArchiveExpiredJobs extends Command
{
    public function handle(): int
    {
        $count = Job::query()
            ->where('status', 'active')
            ->whereDate('closed_on', '<=', today(config('app.schedule_timezone'))->toDateString())
            ->update(['status' => 'archived']);

        $this->info("Archived {$count} expired job(s).");

        return self::SUCCESS;
    }
}
