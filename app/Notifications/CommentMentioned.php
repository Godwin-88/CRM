<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentMentioned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public $model
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notification_preferences['mention_email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'comment_mentioned',
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->model->getMorphClass(),
            'commentable_id' => $this->model->id,
            'comment_excerpt' => str()->limit($this->comment->body, 100),
            'url' => $this->buildUrl(),
        ];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $url = url($this->buildUrl());

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('You were mentioned in a comment')
            ->line($this->comment->user->name.' mentioned you in a comment.')
            ->line('Comment: '.str()->limit($this->comment->body, 200))
            ->action('View Comment', $url);
    }

    protected function buildUrl(): string
    {
        $type = $this->model->getMorphClass();
        $routeName = match ($type) {
            'App\Models\Contact' => 'contacts.show',
            'App\Models\Account' => 'accounts.show',
            'App\Models\Deal' => 'deals.show',
            'App\Models\Ticket' => 'tickets.show',
            'App\Models\Contract' => 'contracts.show',
            'App\Models\Campaign' => 'campaigns.show',
        };

        return route($routeName, $this->model) . '#comment-' . $this->comment->id;
    }
}