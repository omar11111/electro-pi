<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'due_date' => $this->due_date?->toDateString(),
            'is_overdue' => $this->due_date !== null
                && $this->due_date->isPast()
                && $this->status !== TaskStatus::Done,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
