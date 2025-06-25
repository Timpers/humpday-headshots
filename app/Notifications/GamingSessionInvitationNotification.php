<?php

namespace App\Notifications;

use App\Models\GamingSessionInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GamingSessionInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $invitation;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(GamingSessionInvitation $invitation, string $action = 'sent')
    {
        $this->invitation = $invitation->load(['gamingSession.host', 'invitedUser']);
        $this->action = $action; // 'sent', 'accepted', 'declined'
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
        $actionText = match($this->action) {
            'sent' => 'invited you to join',
            'accepted' => 'accepted your invitation to',
            'declined' => 'declined your invitation to',
            default => 'updated your invitation to'
        };

        return (new MailMessage)
                    ->subject('Gaming Session Invitation')
                    ->line($this->invitation->gamingSession->host->name . ' ' . $actionText . ' the gaming session "' . $this->invitation->gamingSession->title . '".')
                    ->line('Game: ' . $this->invitation->gamingSession->game_name)
                    ->line('Scheduled for: ' . $this->invitation->gamingSession->scheduled_at->format('M j, Y \a\t g:i A'))
                    ->when($this->invitation->message, function($mail) {
                        return $mail->line('Message: "' . $this->invitation->message . '"');
                    })
                    ->action('View Session', route('gaming-sessions.show', $this->invitation->gamingSession))
                    ->line('Join the session and have fun gaming!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gaming_session_invitation',
            'invitation_id' => $this->invitation->id,
            'action' => $this->action,
            'session_title' => $this->invitation->gamingSession->title,
            'session_id' => $this->invitation->gaming_session_id,
            'game_name' => $this->invitation->gamingSession->game_name,
            'host_name' => $this->invitation->gamingSession->host->name,
            'host_id' => $this->invitation->gamingSession->host_user_id,
            'scheduled_at' => $this->invitation->gamingSession->scheduled_at->toISOString(),
            'message' => $this->invitation->message,
            'url' => route('gaming-sessions.show', $this->invitation->gamingSession),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $title = match($this->action) {
            'sent' => 'Gaming Session Invitation',
            'accepted' => 'Session Invitation Accepted',
            'declined' => 'Session Invitation Declined',
            default => 'Session Invitation Update'
        };

        $body = match($this->action) {
            'sent' => $this->invitation->gamingSession->host->name . ' invited you to play ' . $this->invitation->gamingSession->game_name,
            'accepted' => $this->invitation->invitedUser->name . ' will join your ' . $this->invitation->gamingSession->game_name . ' session',
            'declined' => $this->invitation->invitedUser->name . ' declined your ' . $this->invitation->gamingSession->game_name . ' session',
            default => 'Gaming session invitation updated'
        };

        return new BroadcastMessage([
            'type' => 'gaming_session_invitation',
            'title' => $title,
            'body' => $body,
            'icon' => '/images/gaming-icon.png',
            'url' => route('gaming-sessions.show', $this->invitation->gamingSession),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
