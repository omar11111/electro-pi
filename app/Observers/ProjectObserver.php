<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    /**
     * Native DB cascadeOnDelete() only fires on a real DELETE row,
     * not on Eloquent's soft delete (which is just an UPDATE).
     * So when a Project is soft-deleted, we soft-delete its Tasks
     * here explicitly to keep the two in sync.
     */
    public function deleting(Project $project): void
    {
        if ($project->isForceDeleting()) {
            return;
        }

        $project->tasks()->delete();
    }

    /**
     * Mirror the same logic when a project is restored, so its
     * tasks come back with it instead of staying soft-deleted.
     */
    public function restored(Project $project): void
    {
        $project->tasks()->onlyTrashed()->restore();
    }
}
