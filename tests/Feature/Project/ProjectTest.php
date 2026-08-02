<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/projects', [
            'name' => 'LMS Platform',
            'description' => 'Backend for the LMS',
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'LMS Platform');

        $this->assertDatabaseHas('projects', [
            'name' => 'LMS Platform',
            'user_id' => $user->id,
        ]);
    }

    public function test_project_creation_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', ['description' => 'no name here'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_only_sees_their_own_projects_in_the_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Project::factory()->count(2)->for($user)->create();
        Project::factory()->count(3)->for($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/projects');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertStatus(403);
    }

    public function test_user_can_update_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Old Name']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/projects/{$project->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_user_cannot_update_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/projects/{$project->id}", ['name' => 'Hacked'])
            ->assertStatus(403);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name]);
    }

    public function test_user_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($project);
    }

    public function test_deleting_a_project_soft_deletes_its_tasks_too(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($task);
    }
}
