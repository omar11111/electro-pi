<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Task;
use App\Models\User;

class DashboardService
{
    public function getStatsForUser(User $user): array
    {
        $projectStats = $user->projects()
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active',
                [ProjectStatus::Active->value]
            )
            ->first();

        $taskStats = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw(
                "COUNT(*) as total,
                 SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN status != 'done' THEN 1 ELSE 0 END) as pending,
                 SUM(CASE WHEN due_date < ? AND status != 'done' THEN 1 ELSE 0 END) as overdue",
                [now()->toDateString()]
            )
            ->first();

        return [
            'total_projects' => (int) $projectStats->total,
            'active_projects' => (int) $projectStats->active,
            'total_tasks' => (int) $taskStats->total,
            'completed_tasks' => (int) $taskStats->completed,
            'pending_tasks' => (int) $taskStats->pending,
            'overdue_tasks' => (int) $taskStats->overdue,
        ];
    }
}
