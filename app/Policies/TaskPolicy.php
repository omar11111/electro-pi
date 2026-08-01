<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Can this user create a task under this specific project?
     * (Task has no direct user_id, so ownership always flows
     * through the parent Project.)
     */
    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id;
    }
}
