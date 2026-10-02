<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * WorkflowAdvanceNotification — generic DB notification sent to the next
 * actor in a workflow when an item advances to their step.
 *
 * Spec §20.
 */
class WorkflowAdvanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public array $data = [],
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'data'  => $this->data,
            'sent_at' => now()->toIso8601String(),
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
