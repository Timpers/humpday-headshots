@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('My Groups') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Your Gaming Groups
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Manage your groups and invitations
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('groups.index') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Browse Groups
                </a>
                <a href="{{ route('groups.create') }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Group
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Owned Groups -->
                @if($ownedGroups->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Groups You Own ({{ $ownedGroups->count() }})
                            </h3>
                            <div class="space-y-4">
                                @foreach($ownedGroups as $group)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3">
                                                <!-- Group Avatar -->
                                                @if($group->avatar)
                                                    <img src="{{ $group->avatar }}" alt="{{ $group->name }}" class="w-12 h-12 rounded-lg">
                                                @else
                                                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-white">
                                                            {{ substr($group->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <!-- Group Info -->
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                        <a href="{{ route('groups.show', $group) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                            {{ $group->name }}
                                                        </a>
                                                    </h4>
                                                    
                                                    <!-- Tags -->
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        @if($group->game)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                {{ $group->game_name }}
                                                            </span>
                                                        @endif
                                                        @if($group->platform)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                                {{ $group->platform_name }}
                                                            </span>
                                                        @endif
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                            Owner
                                                        </span>
                                                    </div>

                                                    <!-- Stats -->
                                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <span>
                                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                            </svg>
                                                            {{ $group->memberships_count }} members
                                                        </span>
                                                        <span>
                                                            {{ $group->is_public ? 'Public' : 'Private' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('groups.edit', $group) }}" 
                                                   class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800">
                                                    Edit
                                                </a>
                                                <a href="{{ route('groups.show', $group) }}" 
                                                   class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Member Groups -->
                @if($memberGroups->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Groups You're In ({{ $memberGroups->count() }})
                            </h3>
                            <div class="space-y-4">
                                @foreach($memberGroups as $group)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3">
                                                <!-- Group Avatar -->
                                                @if($group->avatar)
                                                    <img src="{{ $group->avatar }}" alt="{{ $group->name }}" class="w-12 h-12 rounded-lg">
                                                @else
                                                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-white">
                                                            {{ substr($group->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <!-- Group Info -->
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                        <a href="{{ route('groups.show', $group) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                            {{ $group->name }}
                                                        </a>
                                                    </h4>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        by {{ $group->owner->name }}
                                                    </p>
                                                    
                                                    <!-- Tags -->
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        @if($group->game)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                {{ $group->game_name }}
                                                            </span>
                                                        @endif
                                                        @if($group->platform)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                                {{ $group->platform_name }}
                                                            </span>
                                                        @endif
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                            {{ ucfirst($group->pivot->role) }}
                                                        </span>
                                                    </div>

                                                    <!-- Stats -->
                                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <span>
                                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                            </svg>
                                                            {{ $group->memberships_count }} members                                                        </span>
                                                        <span>
                                                            @php
                                                                $joinedAt = $group->pivot->joined_at;
                                                                if (is_string($joinedAt)) {
                                                                    $joinedAt = \Carbon\Carbon::parse($joinedAt);
                                                                }
                                                            @endphp
                                                            Joined {{ $joinedAt ? $joinedAt->diffForHumans() : 'recently' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('groups.show', $group) }}" 
                                                   class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Empty State -->
                @if($ownedGroups->count() === 0 && $memberGroups->count() === 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                                No groups yet
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                You haven't joined or created any gaming groups yet. Get started by creating your first group!
                            </p>
                            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                                <a href="{{ route('groups.create') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create Group
                                </a>
                                <a href="{{ route('groups.index') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Browse Groups
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pending Invitations -->
                @if($pendingInvitations->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Group Invitations ({{ $pendingInvitations->count() }})
                            </h3>
                            <div class="space-y-3">
                                @foreach($pendingInvitations as $invitation)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="mb-3">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $invitation->group->name }}
                                            </h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                from {{ $invitation->invitedBy->name }}
                                            </p>                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                @php
                                                    $createdAt = $invitation->created_at;
                                                    if (is_string($createdAt)) {
                                                        $createdAt = \Carbon\Carbon::parse($createdAt);
                                                    }
                                                @endphp
                                                {{ $createdAt ? $createdAt->diffForHumans() : 'recently' }}
                                            </p>
                                        </div>

                                        @if($invitation->message)
                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                                "{{ $invitation->message }}"
                                            </p>
                                        @endif

                                        <div class="flex gap-2">
                                            <form action="{{ route('group-invitations.accept', $invitation) }}" method="POST" class="flex-1">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="w-full px-3 py-1 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                                                    Accept
                                                </button>
                                            </form>
                                            <form action="{{ route('group-invitations.decline', $invitation) }}" method="POST" class="flex-1">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="w-full px-3 py-1 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                                    Decline
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($pendingInvitations->count() > 1)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <form action="{{ route('group-invitations.bulk-action') }}" method="POST" class="space-y-2">
                                        @csrf
                                        @foreach($pendingInvitations as $invitation)
                                            <input type="hidden" name="invitation_ids[]" value="{{ $invitation->id }}">
                                        @endforeach
                                        
                                        <div class="flex gap-2">
                                            <button type="submit" name="action" value="accept" 
                                                    class="flex-1 px-3 py-2 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                                                Accept All
                                            </button>
                                            <button type="submit" name="action" value="decline" 
                                                    class="flex-1 px-3 py-2 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                                Decline All
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Your Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Groups Owned</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $ownedGroups->count() }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Groups Joined</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $memberGroups->count() }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Pending Invitations</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $pendingInvitations->count() }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Groups</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $ownedGroups->count() + $memberGroups->count() }}
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
                            <a href="{{ route('groups.create') }}" 
                               class="w-full inline-block text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                                Create New Group
                            </a>
                            <a href="{{ route('groups.index') }}" 
                               class="w-full inline-block text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                                Discover Groups
                            </a>
                            <a href="{{ route('social.friends') }}" 
                               class="w-full inline-block text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                View Friends
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
