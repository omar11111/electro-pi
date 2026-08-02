<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:check-overdue')]
#[Description('Notify project owners about tasks that just became overdue.')]

class CheckOverdueTasks extends Command
{
    public function handle(): int
    {
        $tasks = Task::query()
            ->awaitingOverdueNotification()
            ->with('project.user')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No newly-overdue tasks to notify.');

            return self::SUCCESS;
        }

        foreach ($tasks as $task) {
            // ->notify() queues the job automatically, because
            // TaskOverdueNotification implements ShouldQueue.
            $task->project->user->notify(new TaskOverdueNotification($task));
            // overdue_notified_at مش موجود فى ال test fillable
            $task->forceFill(['overdue_notified_at' => now()])->save();
        }

        $this->info("Queued overdue notifications for {$tasks->count()} task(s).");

        return self::SUCCESS;
    }
}
