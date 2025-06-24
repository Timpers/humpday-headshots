<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index(Request $request)
    {
        $query = Group::with(['owner', 'memberships'])
                     ->withCount('memberships')
                     ->public();

        // Filter by game
        if ($request->filled('game')) {
            $query->byGame($request->game);
        }

        // Filter by platform
        if ($request->filled('platform')) {
            $query->byPlatform($request->platform);
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->orderBy('created_at', 'desc')->paginate(12);

        // Get filter options
        $games = Group::POPULAR_GAMES;
        $platforms = Group::PLATFORMS;

        return view('groups.index', compact('groups', 'games', 'platforms'));
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $games = Group::POPULAR_GAMES;
        $platforms = Group::PLATFORMS;
        
        return view('groups.create', compact('games', 'platforms'));
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'game' => 'nullable|string|max:255',
            'platform' => 'nullable|string|in:' . implode(',', array_keys(Group::PLATFORMS)),
            'is_public' => 'boolean',
            'max_members' => 'integer|min:2|max:500',
        ]);

        DB::transaction(function () use ($request) {
            // Create the group
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
                'game' => $request->game,
                'platform' => $request->platform,
                'owner_id' => Auth::id(),
                'is_public' => $request->boolean('is_public', true),
                'max_members' => $request->integer('max_members', 50),
            ]);

            // Add the creator as owner member
            GroupMembership::create([
                'group_id' => $group->id,
                'user_id' => Auth::id(),
                'role' => GroupMembership::ROLE_OWNER,
                'joined_at' => now(),
            ]);
        });

        return redirect()->route('groups.index')->with('success', 'Group created successfully!');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        $group->load(['owner', 'memberships.user', 'pendingInvitations.invitedUser']);
        
        $user = Auth::user();
        $membership = $group->getMembershipFor($user);
        $canJoin = !$group->hasMember($user) && !$group->isFull() && $group->is_public;
        $canInvite = $membership && $group->canInvite($user);
        
        // Get potential friends to invite (friends who are not already members)
        $friendsToInvite = collect();
        if ($canInvite) {
            // Get friend user IDs
            $friendIds = $user->getFriendIds();
            
            $currentMemberIds = $group->memberships()->pluck('user_id');
            $pendingInviteIds = $group->pendingInvitations()->pluck('invited_user_id');
            
            $excludeIds = $currentMemberIds->merge($pendingInviteIds)->unique();
            
            $friendsToInvite = User::whereIn('id', $friendIds)
                                  ->whereNotIn('id', $excludeIds)
                                  ->get();
        }

        return view('groups.show', compact('group', 'membership', 'canJoin', 'canInvite', 'friendsToInvite'));
    }

    /**
     * Show the form for editing the group.
     */
    public function edit(Group $group)
    {
        // Check if user can edit this group
        if (!$group->isAdmin(Auth::user())) {
            abort(403, 'You do not have permission to edit this group.');
        }

        $games = Group::POPULAR_GAMES;
        $platforms = Group::PLATFORMS;
        
        return view('groups.edit', compact('group', 'games', 'platforms'));
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, Group $group)
    {
        // Check if user can edit this group
        if (!$group->isAdmin(Auth::user())) {
            abort(403, 'You do not have permission to edit this group.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'game' => 'nullable|string|max:255',
            'platform' => 'nullable|string|in:' . implode(',', array_keys(Group::PLATFORMS)),
            'is_public' => 'boolean',
            'max_members' => 'integer|min:2|max:500',
        ]);

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
            'game' => $request->game,
            'platform' => $request->platform,
            'is_public' => $request->boolean('is_public', true),
            'max_members' => $request->integer('max_members', 50),
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Group updated successfully!');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group)
    {
        // Only the owner can delete the group
        if (!$group->isOwner(Auth::user())) {
            abort(403, 'Only the group owner can delete this group.');
        }

        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Group deleted successfully!');
    }

    /**
     * Join a public group.
     */
    public function join(Group $group)
    {
        $user = Auth::user();

        // Check if user can join
        if ($group->hasMember($user)) {
            return back()->with('error', 'You are already a member of this group.');
        }

        if ($group->isFull()) {
            return back()->with('error', 'This group is full.');
        }

        if (!$group->is_public) {
            return back()->with('error', 'This group is private. You need an invitation to join.');
        }

        // Add user to group
        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_MEMBER,
            'joined_at' => now(),
        ]);

        return back()->with('success', 'You have successfully joined the group!');
    }

    /**
     * Leave a group.
     */
    public function leave(Group $group)
    {
        $user = Auth::user();

        // Check if user is a member
        $membership = $group->getMembershipFor($user);
        if (!$membership) {
            return back()->with('error', 'You are not a member of this group.');
        }

        // Owner cannot leave their own group
        if ($group->isOwner($user)) {
            return back()->with('error', 'Group owners cannot leave their group. Transfer ownership or delete the group instead.');
        }

        $membership->delete();

        return redirect()->route('groups.index')->with('success', 'You have left the group.');
    }

    /**
     * Show user's groups (my groups page).
     */
    public function myGroups()
    {
        $user = Auth::user();
        
        $ownedGroups = $user->ownedGroups()->withCount('memberships')->get();
        $memberGroups = $user->groups()->wherePivot('role', '!=', 'owner')->withCount('memberships')->get();
        $pendingInvitations = $user->pendingGroupInvitations()->with('group.owner')->get();

        return view('groups.my-groups', compact('ownedGroups', 'memberGroups', 'pendingInvitations'));
    }
}
