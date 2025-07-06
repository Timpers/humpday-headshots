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

    public $invitationId;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(GamingSessionInvitation $invitation, string $action = 'sent')
    {
        $this->invitationId = $invitation->id;
        $this->action = $action; // 'sent', 'accepted', 'declined'
    }

    /**
     * Get the invitation with required relationships.
     */
    public function getInvitation(): GamingSessionInvitation
    {
        return GamingSessionInvitation::with(['gamingSession.host', 'invitedUser'])
            ->findOrFail($this->invitationId);
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
        $invitation = $this->getInvitation();
        
        $actionText = match($this->action) {
            'sent' => 'invited you to join',
            'accepted' => 'accepted your invitation to',
            'declined' => 'declined your invitation to',
            default => 'updated your invitation to'
        };

        return (new MailMessage)
                    ->subject('Gaming Session Invitation')
                    ->line($invitation->gamingSession->host->name . ' ' . $actionText . ' the gaming session "' . $invitation->gamingSession->title . '".')
                    ->line('Game: ' . $invitation->gamingSession->game_name)
                    ->line('Scheduled for: ' . $invitation->gamingSession->scheduled_at->format('M j, Y \a\t g:i A'))
                    ->when($invitation->message, function($mail) use ($invitation) {
                        return $mail->line('Message: "' . $invitation->message . '"');
                    })
                    ->action('View Session', route('gaming-sessions.show', $invitation->gamingSession))
                    ->line('Join the session and have fun gaming!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $invitation = $this->getInvitation();
        
        return [
            'type' => 'gaming_session_invitation',
            'invitation_id' => $invitation->id,
            'action' => $this->action,
            'session_title' => $invitation->gamingSession->title,
            'session_id' => $invitation->gaming_session_id,
            'game_name' => $invitation->gamingSession->game_name,
            'host_name' => $invitation->gamingSession->host->name,
            'host_id' => $invitation->gamingSession->host_user_id,
            'scheduled_at' => $invitation->gamingSession->scheduled_at->toISOString(),
            'message' => $invitation->message,
            'url' => route('gaming-sessions.show', $invitation->gamingSession),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $invitation = $this->getInvitation();
        
        $title = match($this->action) {
            'sent' => 'Gaming Session Invitation',
            'accepted' => 'Session Invitation Accepted',
            'declined' => 'Session Invitation Declined',
            default => 'Session Invitation Update'
        };

        $body = match($this->action) {
            'sent' => $invitation->gamingSession->host->name . ' invited you to play ' . $invitation->gamingSession->game_name,
            'accepted' => $invitation->invitedUser->name . ' will join your ' . $invitation->gamingSession->game_name . ' session',
            'declined' => $invitation->invitedUser->name . ' declined your ' . $invitation->gamingSession->game_name . ' session',
            default => 'Gaming session invitation updated'
        };

        return new BroadcastMessage([
            'type' => 'gaming_session_invitation',
            'title' => $title,
            'body' => $body,
            'icon' => '/images/gaming-icon.png',
            'url' => route('gaming-sessions.show', $invitation->gamingSession),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
