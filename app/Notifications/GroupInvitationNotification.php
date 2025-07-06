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

    public $invitationId;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(GroupInvitation $invitation, string $action = 'sent')
    {
        $this->invitationId = $invitation->id;
        $this->action = $action; // 'sent', 'accepted', 'declined'
    }

    /**
     * Get the invitation model.
     */
    public function getInvitation(): GroupInvitation
    {
        return GroupInvitation::with(['group', 'invitedUser', 'invitedBy'])->find($this->invitationId);
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
                    ->subject('Group Invitation Update')
                    ->line($invitation->invitedBy->name . ' ' . $actionText . ' the group "' . $invitation->group->name . '".')
                    ->when($invitation->message, function($mail) use ($invitation) {
                        return $mail->line('Message: "' . $invitation->message . '"');
                    })
                    ->action('View Group', route('groups.show', $invitation->group))
                    ->line('Join the group to connect with other members!');
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
            'type' => 'group_invitation',
            'invitation_id' => $invitation->id,
            'action' => $this->action,
            'group_name' => $invitation->group->name,
            'group_id' => $invitation->group_id,
            'inviter_name' => $invitation->invitedBy->name,
            'inviter_id' => $invitation->invited_by_user_id,
            'message' => $invitation->message,
            'url' => route('groups.show', $invitation->group),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $invitation = $this->getInvitation();
        
        $title = match($this->action) {
            'sent' => 'Group Invitation',
            'accepted' => 'Group Invitation Accepted',
            'declined' => 'Group Invitation Declined',
            default => 'Group Invitation Update'
        };

        $body = match($this->action) {
            'sent' => $invitation->invitedBy->name . ' invited you to join ' . $invitation->group->name,
            'accepted' => $invitation->invitedUser->name . ' joined ' . $invitation->group->name,
            'declined' => $invitation->invitedUser->name . ' declined to join ' . $invitation->group->name,
            default => 'Group invitation updated'
        };

        return new BroadcastMessage([
            'type' => 'group_invitation',
            'title' => $title,
            'body' => $body,
            'icon' => '/images/group-icon.png',
            'url' => route('groups.show', $invitation->group),
            'data' => $this->toArray($notifiable),
        ]);
    }
}
