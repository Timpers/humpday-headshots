<?php

namespace App\Notifications;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation as GamingSessionInvitationModel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GamingSessionInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invitation;
    protected $gamingSession;
    protected $inviter;

    /**
     * Create a new notification instance.
     */
    public function __construct(GamingSessionInvitationModel $invitation)
    {
        $this->invitation = $invitation;
        $this->gamingSession = $invitation->gamingSession;
        $this->inviter = $invitation->invitedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🎮 You're invited to a gaming session!")
            ->greeting("Hey {$notifiable->name}!")
            ->line("{$this->inviter->name} has invited you to join a gaming session:")
            ->line("**{$this->gamingSession->title}**")
            ->line("🎯 Game: {$this->gamingSession->game_name}")
            ->line("📅 When: {$this->gamingSession->scheduled_at->format('M j, Y g:i A')}")
            ->line("👥 Max participants: {$this->gamingSession->max_participants}")
            ->when($this->gamingSession->description, function ($message) {
                return $message->line("📝 Description: {$this->gamingSession->description}");
            })
            ->when($this->gamingSession->requirements, function ($message) {
                return $message->line("⚠️ Requirements: {$this->gamingSession->requirements}");
            })
            ->action('View Session & Respond', route('gaming-sessions.show', $this->gamingSession))
            ->line('Click the button above to view the session details and accept or decline the invitation.')
            ->line('Thanks for being part of our gaming community!');
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
            'gaming_session_id' => $this->gamingSession->id,
            'gaming_session_title' => $this->gamingSession->title,
            'game_name' => $this->gamingSession->game_name,
            'scheduled_at' => $this->gamingSession->scheduled_at,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'message' => "{$this->inviter->name} invited you to join '{$this->gamingSession->title}'"
        ];
    }
}
