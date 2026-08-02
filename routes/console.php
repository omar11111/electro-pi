<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs once a day; the command itself is idempotent (skips tasks
// that already have overdue_notified_at set), so re-running it
// safely is never a concern even if the scheduler restarts.
Schedule::command('tasks:check-overdue')->daily();
