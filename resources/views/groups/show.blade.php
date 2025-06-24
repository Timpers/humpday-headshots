@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ $group->name }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Group Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <!-- Group Info -->
                    <div class="flex-1">
                        <div class="flex items-start gap-4">
                            <!-- Group Avatar -->
                            @if($group->avatar)
                                <img src="{{ $group->avatar }}" alt="{{ $group->name }}" class="w-20 h-20 rounded-lg">
                            @else
                                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl font-bold text-white">
                                        {{ substr($group->name, 0, 1) }}
                                    </span>
                                </div>
                            @endif

                            <!-- Group Details -->
                            <div class="flex-1">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ $group->name }}
                                </h1>
                                
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    Created by {{ $group->owner->name }} • {{ $group->created_at->diffForHumans() }}
                                </p>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @if($group->game)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            🎮 {{ $group->game_name }}
                                        </span>
                                    @endif
                                    @if($group->platform)
                                        @php
                                            $platformColors = [
                                                'steam' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'xbox_live' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'playstation_network' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                                                'nintendo_online' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'battlenet' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                                'cross_platform' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                            ];
                                            $colorClass = $platformColors[$group->platform] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $colorClass }}">
                                            🎯 {{ $group->platform_name }}
                                        </span>
                                    @endif
                                    @if(!$group->is_public)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                            🔒 Private
                                        </span>
                                    @endif
                                </div>

                                <!-- Description -->
                                @if($group->description)
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        {{ $group->description }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Action Panel -->
                    <div class="lg:w-80">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-4">
                            <!-- Member Count -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $group->member_count }}/{{ $group->max_members }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Members
                                    @if($group->isFull())
                                        <span class="text-red-500 font-medium">• Full</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-2">
                                @if($membership)
                                    <!-- User is a member -->
                                    <div class="text-center p-3 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
                                        <div class="font-medium">You are a {{ $membership->role_name }}</div>
                                        <div class="text-sm">Joined {{ $membership->joined_at->diffForHumans() }}</div>
                                    </div>
                                    
                                    @if(!$group->isOwner(Auth::user()))
                                        <form action="{{ route('groups.leave', $group) }}" method="POST" class="w-full">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to leave this group?')"
                                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500">
                                                Leave Group
                                            </button>
                                        </form>
                                    @endif

                                    @if($group->isAdmin(Auth::user()))
                                        <a href="{{ route('groups.edit', $group) }}" 
                                           class="w-full inline-block text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                                            Edit Group
                                        </a>
                                    @endif
                                @elseif($canJoin)
                                    <!-- User can join -->
                                    <form action="{{ route('groups.join', $group) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                                            Join Group
                                        </button>
                                    </form>
                                @elseif($group->hasPendingInvitation(Auth::user()))
                                    <!-- User has pending invitation -->
                                    <div class="text-center p-3 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg">
                                        <div class="font-medium">Invitation Pending</div>
                                        <div class="text-sm">Check your invitations</div>
                                    </div>
                                @elseif($group->isFull())
                                    <!-- Group is full -->
                                    <div class="text-center p-3 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
                                        <div class="font-medium">Group Full</div>
                                        <div class="text-sm">No spots available</div>
                                    </div>
                                @elseif(!$group->is_public)
                                    <!-- Private group -->
                                    <div class="text-center p-3 bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg">
                                        <div class="font-medium">Private Group</div>
                                        <div class="text-sm">Invitation required</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Members List -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Members ({{ $group->member_count }})
                            </h3>
                            
                            @if($canInvite)
                                <button onclick="toggleInviteModal()" 
                                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Invite Friends
                                </button>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @foreach($group->memberships as $membershipItem)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ substr($membershipItem->user->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $membershipItem->user->name }}
                                                @if($membershipItem->user->id === Auth::id())
                                                    <span class="text-sm text-gray-500">(You)</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $membershipItem->role_name }} • Joined {{ $membershipItem->joined_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($membershipItem->role === 'owner')
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                            Owner
                                        </span>
                                    @elseif($membershipItem->role === 'admin')
                                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 rounded-full">
                                            Admin
                                        </span>
                                    @elseif($membershipItem->role === 'moderator')
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full">
                                            Moderator
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pending Invitations (for admins) -->
                @if($canInvite && $group->pendingInvitations->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Pending Invitations ({{ $group->pendingInvitations->count() }})
                            </h3>
                            <div class="space-y-3">
                                @foreach($group->pendingInvitations as $invitation)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ substr($invitation->invitedUser->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $invitation->invitedUser->name }}
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                                    Invited {{ $invitation->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        <form action="{{ route('group-invitations.cancel', $invitation) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                                Cancel
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Group Stats -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Group Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Created</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $group->created_at->format('M j, Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Members</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $group->member_count }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Available Spots</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $group->max_members - $group->member_count }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Privacy</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $group->is_public ? 'Public' : 'Private' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Quick Actions
                        </h3>
                        <div class="space-y-2">
                            <a href="{{ route('groups.index') }}" 
                               class="w-full inline-block text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                Browse Groups
                            </a>
                            <a href="{{ route('groups.my-groups') }}" 
                               class="w-full inline-block text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                My Groups
                            </a>
                            @if($group->game)
                                <a href="{{ route('groups.index', ['game' => $group->game]) }}" 
                                   class="w-full inline-block text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                    More {{ $group->game_name }} Groups
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invite Modal -->
@if($canInvite && $friendsToInvite->count() > 0)
    <div id="inviteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Invite Friends
                        </h3>
                        <button onclick="toggleInviteModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form action="{{ route('group-invitations.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $group->id }}">
                        
                        <div class="mb-4">
                            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Friend
                            </label>
                            <select name="user_id" id="user_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Choose a friend...</option>
                                @foreach($friendsToInvite as $friend)
                                    <option value="{{ $friend->id }}">{{ $friend->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Message (optional)
                            </label>
                            <textarea name="message" id="message" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                      placeholder="Hey! Join our gaming group..."></textarea>
                        </div>
                        
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="toggleInviteModal()" 
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Send Invitation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
function toggleInviteModal() {
    const modal = document.getElementById('inviteModal');
    modal.classList.toggle('hidden');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('inviteModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
});
</script>
@endpush
@endsection
