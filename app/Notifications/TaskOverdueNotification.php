<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Task $task)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task overdue: {$this->task->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your task \"{$this->task->title}\" in project \"{$this->task->project->name}\" is now overdue.")
            ->line('Due date: '.$this->task->due_date->toFormattedDateString())
            ->line('Current status: '.$this->task->status->value)
            ->action('View Task', url("/projects/{$this->task->project_id}/tasks/{$this->task->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'title' => $this->task->title,
            'due_date' => $this->task->due_date->toDateString(),
        ];
    }
}
