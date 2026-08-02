<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(TaskFactory::class)]
#[Fillable([
    'project_id',
    'title',
    'description',
    'priority',
    'status',
    'due_date',
])]
#[Casts([
    'due_date' => 'date',
    'priority' => TaskPriority::class,
    'status' => TaskStatus::class,
    'overdue_notified_at' => 'datetime',
])]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Overdue = due_date has passed and task isn't done yet.
     * Reused as-is by both the "Filter by Status" style queries
     * and the Dashboard endpoint's overdue count.
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query
            ->whereDate('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done);
    }

    /**
     * Overdue tasks that haven't triggered a notification yet.
     * Used by the tasks:check-overdue scheduled command so the
     * same task never gets notified twice.
     */
    #[Scope]
    protected function awaitingOverdueNotification(Builder $query): void
    {
        $query
            ->overdue()
            ->whereNull('overdue_notified_at');
    }
}
