<?php

namespace App\Notifications;

use App\Models\GamingSessionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GamingSessionMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(GamingSessionMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New message in ' . $this->message->gamingSession->title)
                    ->line($this->message->user->name . ' posted a new message in the gaming session "' . $this->message->gamingSession->title . '".')
                    ->line('"' . $this->message->message . '"')
                    ->action('View Messages', route('gaming-sessions.messages.index', $this->message->gamingSession))
                    ->line('Join the conversation!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gaming_session_message',
            'message_id' => $this->message->id,
            'session_id' => $this->message->gaming_session_id,
            'session_title' => $this->message->gamingSession->title,
            'sender_name' => $this->message->user->name,
            'sender_id' => $this->message->user_id,
            'message_preview' => substr($this->message->message, 0, 100),
            'url' => route('gaming-sessions.messages.index', $this->message->gamingSession),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'gaming_session_message',
            'title' => 'New Message in ' . $this->message->gamingSession->title,
            'body' => $this->message->user->name . ': ' . substr($this->message->message, 0, 50) . '...',
            'icon' => '/images/message-icon.png',
            'url' => route('gaming-sessions.messages.index', $this->message->gamingSession),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
