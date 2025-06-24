@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Group Invitation
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Group Invitation
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $invitation->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($invitation->status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300') }}">
                        {{ ucfirst($invitation->status) }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                <!-- Group Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Group Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            {{ $invitation->group->name }}
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            {{ $invitation->group->description }}
                        </p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Privacy:</span>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ ucfirst($invitation->group->privacy) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Members:</span>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invitation->group->members_count ?? $invitation->group->members()->count() }}/{{ $invitation->group->max_members ?? '∞' }}
                                </div>
                            </div>
                        </div>

                        @if($invitation->group->game)
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Game:</span>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invitation->group->game }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Invitation Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Invitation Details
                        </h4>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Invited by:</span>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invitation->invitedBy->name }}
                                </div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Date sent:</span>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invitation->created_at->format('M j, Y \a\t g:i A') }}
                                </div>
                            </div>

                            @if($invitation->message)
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Message:</span>
                                    <div class="text-gray-900 dark:text-gray-100 italic">
                                        "{{ $invitation->message }}"
                                    </div>
                                </div>
                            @endif

                            @if($invitation->status !== 'pending')
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $invitation->status === 'accepted' ? 'Accepted' : 'Declined' }} on:
                                    </span>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $invitation->updated_at->format('M j, Y \a\t g:i A') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                @if($invitation->status === 'pending')
                    <div class="flex justify-center gap-4">
                        <form method="POST" action="{{ route('group-invitations.accept', $invitation) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 transition-colors text-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Accept Invitation
                            </button>
                        </form>

                        <form method="POST" action="{{ route('group-invitations.decline', $invitation) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 transition-colors text-lg" 
                                    onclick="return confirm('Are you sure you want to decline this invitation?')">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Decline
                            </button>
                        </form>
                    </div>
                @else
                    <div class="text-center">
                        <div class="mb-4">
                            @if($invitation->status === 'accepted')
                                <div class="text-green-600 dark:text-green-400 text-lg">
                                    ✅ You have accepted this invitation and joined the group.
                                </div>
                            @elseif($invitation->status === 'declined')
                                <div class="text-red-600 dark:text-red-400 text-lg">
                                    ❌ You have declined this invitation.
                                </div>
                            @else
                                <div class="text-gray-600 dark:text-gray-400 text-lg">
                                    🚫 This invitation has been cancelled.
                                </div>
                            @endif
                        </div>
                        
                        @if($invitation->status === 'accepted')
                            <a href="{{ route('groups.show', $invitation->group) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                View Group
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Back Button -->
                <div class="text-center mt-6">
                    <a href="{{ route('groups.my-invitations') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to My Invitations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
