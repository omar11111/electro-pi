<?php

namespace Tests\Feature\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_counts_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $activeProject = Project::factory()->for($user)->create(['status' => 'active']);
        Project::factory()->for($user)->create(['status' => 'archived']);

        Task::factory()->count(2)->for($activeProject)->done()->create(['due_date' => now()->subDays(5)]);
        Task::factory()->count(3)->for($activeProject)->create(['status' => 'todo', 'due_date' => now()->addDays(5)]);
        Task::factory()->count(2)->for($activeProject)->overdue()->create();

        // Belongs to a different account — must never affect these numbers.
        $otherUser = User::factory()->create();
        Project::factory()->for($otherUser)->has(Task::factory()->count(10))->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard');

        $response->assertOk()->assertJson([
            'total_projects' => 2,
            'active_projects' => 1,
            'total_tasks' => 7,
            'completed_tasks' => 2,
            'pending_tasks' => 5,
            'overdue_tasks' => 2,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }
}
