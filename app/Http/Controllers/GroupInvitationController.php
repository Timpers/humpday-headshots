<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupInvitationController extends Controller
{
    /**
     * Display all invitations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $status = $request->get('status', 'all');
        $invitations = $user->receivedGroupInvitations()
            ->with(['group.owner', 'invitedBy'])
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('groups.invitations.index', compact('invitations', 'status'));
    }

    /**
     * Send a group invitation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $group = Group::findOrFail($request->group_id);
        $invitedUser = User::findOrFail($request->user_id);
        $inviter = Auth::user();

        // Check if user can invite
        if (!$group->canInvite($inviter)) {
            return back()->with('error', 'You do not have permission to invite users to this group.');
        }

        // Check if user is already a member
        if ($group->hasMember($invitedUser)) {
            return back()->with('error', 'This user is already a member of the group.');
        }

        // Check if there's already a pending invitation
        if ($group->hasPendingInvitation($invitedUser)) {
            return back()->with('error', 'This user already has a pending invitation to this group.');
        }

        // Check if group is full
        if ($group->isFull()) {
            return back()->with('error', 'This group is full.');
        }

        // Create the invitation
        $invitation = GroupInvitation::create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $inviter->id,
            'message' => $request->message,
        ]);

        // Send notification to invited user
        $invitedUser->notify(new GroupInvitationNotification($invitation));

        return back()->with('success', "Invitation sent to {$invitedUser->name}!");
    }

    /**
     * Accept a group invitation.
     */
    public function accept(GroupInvitation $invitation)
    {
        $user = Auth::user();

        // Check if the invitation belongs to the current user
        if ($invitation->invited_user_id !== $user->id) {
            abort(403, 'You cannot accept this invitation.');
        }

        // Check if invitation is still pending
        if (!$invitation->isPending()) {
            return back()->with('error', 'This invitation is no longer valid.');
        }

        // Check if group is full
        if ($invitation->group->isFull()) {
            return back()->with('error', 'This group is now full.');
        }

        DB::transaction(function () use ($invitation, $user) {
            // Accept the invitation
            $invitation->accept();

            // Add user to group
            GroupMembership::create([
                'group_id' => $invitation->group_id,
                'user_id' => $user->id,
                'role' => GroupMembership::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
        });

        return back()->with('success', "You have joined {$invitation->group->name}!");
    }

    /**
     * Decline a group invitation.
     */
    public function decline(GroupInvitation $invitation)
    {
        $user = Auth::user();

        // Check if the invitation belongs to the current user
        if ($invitation->invited_user_id !== $user->id) {
            abort(403, 'You cannot decline this invitation.');
        }

        // Check if invitation is still pending
        if (!$invitation->isPending()) {
            return back()->with('error', 'This invitation is no longer valid.');
        }

        $invitation->decline();

        return back()->with('success', 'Invitation declined.');
    }

    /**
     * Cancel a group invitation (by the inviter or group admin).
     */
    public function cancel(GroupInvitation $invitation)
    {
        $user = Auth::user();
        $group = $invitation->group;

        // Check if user can cancel this invitation
        if ($invitation->invited_by_user_id !== $user->id && !$group->isAdmin($user)) {
            abort(403, 'You cannot cancel this invitation.');
        }

        // Check if invitation is still pending
        if (!$invitation->isPending()) {
            return back()->with('error', 'This invitation is no longer valid.');
        }

        $invitation->cancel();

        return back()->with('success', 'Invitation cancelled.');
    }

    /**
     * Show invitation details.
     */
    public function show(GroupInvitation $invitation)
    {
        $user = Auth::user();

        // Check if user can view this invitation
        if ($invitation->invited_user_id !== $user->id) {
            abort(403, 'You cannot view this invitation.');
        }

        $invitation->load(['group.owner', 'invitedBy']);

        return view('groups.invitations.show', compact('invitation'));
    }

    /**
     * Bulk accept/decline invitations.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:accept,decline',
            'invitation_ids' => 'required|array',
            'invitation_ids.*' => 'exists:group_invitations,id',
        ]);

        $user = Auth::user();
        $invitations = GroupInvitation::whereIn('id', $request->invitation_ids)
                                     ->where('invited_user_id', $user->id)
                                     ->where('status', 'pending')
                                     ->get();

        $successCount = 0;
        $action = $request->action;

        DB::transaction(function () use ($invitations, $action, $user, &$successCount) {
            foreach ($invitations as $invitation) {
                if ($action === 'accept') {
                    // Check if group is not full
                    if (!$invitation->group->isFull()) {
                        $invitation->accept();
                        
                        GroupMembership::create([
                            'group_id' => $invitation->group_id,
                            'user_id' => $user->id,
                            'role' => GroupMembership::ROLE_MEMBER,
                            'joined_at' => now(),
                        ]);
                        
                        $successCount++;
                    }
                } else {
                    $invitation->decline();
                    $successCount++;
                }
            }
        });

        $actionPast = $action === 'accept' ? 'accepted' : 'declined';
        return back()->with('success', "{$successCount} invitation(s) {$actionPast}.");
    }
}
