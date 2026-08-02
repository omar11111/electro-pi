<?php

namespace Tests\Feature\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task_under_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Write ERD',
            'priority' => 'high',
            'status' => 'todo',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Write ERD');
        $this->assertDatabaseHas('tasks', ['title' => 'Write ERD', 'project_id' => $project->id]);
    }

    public function test_user_cannot_create_task_under_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", ['title' => 'Sneaky Task'])
            ->assertStatus(403);
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->count(2)->for($project)->create(['status' => 'done']);
        Task::factory()->count(3)->for($project)->create(['status' => 'todo']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?status=done");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->count(2)->for($project)->create(['priority' => 'high']);
        Task::factory()->count(4)->for($project)->create(['priority' => 'low']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?priority=high");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_tasks_can_be_searched_by_title(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->for($project)->create(['title' => 'Design database schema']);
        Task::factory()->for($project)->create(['title' => 'Write unit tests']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?search=database");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Design database schema', $response->json('data.0.title'));
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?status=not-a-real-status")
            ->assertStatus(422);
    }

    public function test_user_cannot_update_task_in_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();
        $task = Task::factory()->for($project)->create();

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/tasks/{$task->id}", ['title' => 'Hacked title'])
            ->assertStatus(403);
    }

    public function test_user_can_delete_their_own_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($task);
    }
}
