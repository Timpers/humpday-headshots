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

    public $messageId;

    /**
     * Create a new notification instance.
     */
    public function __construct(GamingSessionMessage $message)
    {
        $this->messageId = $message->id;
    }

    /**
     * Get the message with required relationships.
     */
    public function getMessage(): GamingSessionMessage
    {
        return GamingSessionMessage::with(['gamingSession', 'user'])
            ->findOrFail($this->messageId);
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
        $message = $this->getMessage();
        
        return (new MailMessage)
                    ->subject('New message in ' . $message->gamingSession->title)
                    ->line($message->user->name . ' posted a new message in the gaming session "' . $message->gamingSession->title . '".')
                    ->line('"' . $message->message . '"')
                    ->action('View Messages', route('gaming-sessions.messages.index', $message->gamingSession))
                    ->line('Join the conversation!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = $this->getMessage();
        
        return [
            'type' => 'gaming_session_message',
            'message_id' => $message->id,
            'session_id' => $message->gaming_session_id,
            'session_title' => $message->gamingSession->title,
            'sender_name' => $message->user->name,
            'sender_id' => $message->user_id,
            'message_preview' => substr($message->message, 0, 100),
            'url' => route('gaming-sessions.messages.index', $message->gamingSession),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $message = $this->getMessage();
        
        return new BroadcastMessage([
            'type' => 'gaming_session_message',
            'title' => 'New Message in ' . $message->gamingSession->title,
            'body' => $message->user->name . ': ' . substr($message->message, 0, 50) . '...',
            'icon' => '/images/message-icon.png',
            'url' => route('gaming-sessions.messages.index', $message->gamingSession),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
