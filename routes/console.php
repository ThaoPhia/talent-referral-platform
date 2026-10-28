<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('jobs:archive-expired')->dailyAt('23:59')->withoutOverlapping();
