<?php

namespace App\Notifications;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $invitation;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(GroupInvitation $invitation, string $action = 'sent')
    {
        $this->invitation = $invitation->load(['group', 'invitedUser', 'invitedBy']);
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
                    ->subject('Group Invitation Update')
                    ->line($this->invitation->invitedBy->name . ' ' . $actionText . ' the group "' . $this->invitation->group->name . '".')
                    ->when($this->invitation->message, function($mail) {
                        return $mail->line('Message: "' . $this->invitation->message . '"');
                    })
                    ->action('View Group', route('groups.show', $this->invitation->group))
                    ->line('Join the group to connect with other members!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_invitation',
            'invitation_id' => $this->invitation->id,
            'action' => $this->action,
            'group_name' => $this->invitation->group->name,
            'group_id' => $this->invitation->group_id,
            'inviter_name' => $this->invitation->invitedBy->name,
            'inviter_id' => $this->invitation->invited_by_user_id,
            'message' => $this->invitation->message,
            'url' => route('groups.show', $this->invitation->group),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $title = match($this->action) {
            'sent' => 'Group Invitation',
            'accepted' => 'Group Invitation Accepted',
            'declined' => 'Group Invitation Declined',
            default => 'Group Invitation Update'
        };

        $body = match($this->action) {
            'sent' => $this->invitation->invitedBy->name . ' invited you to join ' . $this->invitation->group->name,
            'accepted' => $this->invitation->invitedUser->name . ' joined ' . $this->invitation->group->name,
            'declined' => $this->invitation->invitedUser->name . ' declined to join ' . $this->invitation->group->name,
            default => 'Group invitation updated'
        };

        return new BroadcastMessage([
            'type' => 'group_invitation',
            'title' => $title,
            'body' => $body,
            'icon' => '/images/group-icon.png',
            'url' => route('groups.show', $this->invitation->group),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
