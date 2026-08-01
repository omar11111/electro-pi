<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Known credentials so whoever reviews this can log in directly.
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $projects = Project::factory()
            ->count(5)
            ->for($demoUser)
            ->create();

        foreach ($projects as $project) {
            Task::factory()->count(8)->for($project)->create();
        }

        // A few guaranteed-overdue tasks so /api/dashboard has something
        // real to report, instead of relying on random luck from the factory.
        Task::factory()->count(3)->overdue()->for($projects->first())->create();

        // A second, unrelated user + data — proves projects/tasks stay
        // scoped per-user and don't leak across accounts.
        $otherUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
        ]);

        Project::factory()
            ->count(2)
            ->for($otherUser)
            ->has(Task::factory()->count(5))
            ->create();
    }
}
