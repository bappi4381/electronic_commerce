<?php

namespace App\Notifications\Admin;

use Illuminate\Notifications\Notification;

/**
 * Database notification shown in the admin top-bar bell.
 * Subclasses build the payload once in their constructor so it is shared by every recipient.
 */
abstract class AdminNotification extends Notification
{
    /** @var array{kind: string, title: string, body: string, url: string, icon: string, color: string} */
    protected array $data;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
