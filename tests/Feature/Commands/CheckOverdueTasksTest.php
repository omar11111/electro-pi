<?php

namespace Tests\Feature\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckOverdueTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_the_project_owner_for_overdue_tasks(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $overdueTask = Task::factory()->for($project)->overdue()->create();
        Task::factory()->for($project)->create([
            'status' => 'todo',
            'due_date' => now()->addDays(3), // not overdue yet
        ]);

        $this->artisan('tasks:check-overdue')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            TaskOverdueNotification::class,
            fn ($notification) => $notification->task->is($overdueTask)
        );

        Notification::assertSentTimes(TaskOverdueNotification::class, 1);

        $this->assertNotNull($overdueTask->fresh()->overdue_notified_at);
    }

    public function test_it_does_not_notify_the_same_task_twice(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->overdue()->create();

        $this->artisan('tasks:check-overdue');
        $this->artisan('tasks:check-overdue');

        Notification::assertSentTimes(TaskOverdueNotification::class, 1);
    }

    public function test_it_does_nothing_when_no_tasks_are_overdue(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->create(['due_date' => now()->addDays(5)]);

        $this->artisan('tasks:check-overdue')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
