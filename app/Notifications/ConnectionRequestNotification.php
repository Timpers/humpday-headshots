<?php

namespace App\Notifications;

use App\Models\UserConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $connectionId;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserConnection $connection, string $action = 'sent')
    {
        $this->connectionId = $connection->id;
        $this->action = $action; // 'sent', 'accepted', 'declined'
    }

    /**
     * Get the connection model.
     */
    public function getConnection(): UserConnection
    {
        return UserConnection::with('requester')->find($this->connectionId);
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
        $connection = $this->getConnection();
        
        $actionText = match($this->action) {
            'sent' => 'sent you a connection request',
            'accepted' => 'accepted your connection request',
            'declined' => 'declined your connection request',
            default => 'updated your connection request'
        };

        return (new MailMessage)
                    ->subject('Connection Request Update')
                    ->line($connection->requester->name . ' ' . $actionText . '.')
                    ->when($connection->message, function($mail) use ($connection) {
                        return $mail->line('Message: "' . $connection->message . '"');
                    })
                    ->action('View Connections', route('social.requests'))
                    ->line('Manage your connections in your social hub.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $connection = $this->getConnection();
        
        return [
            'type' => 'connection_request',
            'connection_id' => $connection->id,
            'action' => $this->action,
            'requester_name' => $connection->requester->name,
            'requester_id' => $connection->requester_id,
            'message' => $connection->message,
            'url' => route('social.requests'),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $connection = $this->getConnection();
        
        $title = match($this->action) {
            'sent' => 'New Connection Request',
            'accepted' => 'Connection Request Accepted',
            'declined' => 'Connection Request Declined',
            default => 'Connection Update'
        };

        $body = match($this->action) {
            'sent' => $connection->requester->name . ' wants to connect with you',
            'accepted' => $connection->requester->name . ' accepted your connection request',
            'declined' => $connection->requester->name . ' declined your connection request',
            default => 'Connection request updated'
        };

        return new BroadcastMessage([
            'type' => 'connection_request',
            'title' => $title,
            'body' => $body,
            'icon' => '/images/connection-icon.png',
            'url' => route('social.requests'),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
