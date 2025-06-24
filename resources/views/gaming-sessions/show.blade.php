@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $gamingSession->title }}</h1>
                <p class="text-xl text-blue-600 dark:text-blue-400 font-medium mb-4">{{ $gamingSession->game_name }}</p>
                
                <!-- Session Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ $gamingSession->scheduled_at->format('M j, Y \a\t g:i A') }}
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Hosted by {{ $gamingSession->host->name }}
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        {{ $gamingSession->participants->count() }}/{{ $gamingSession->max_participants }} participants
                    </div>
                </div>
            </div>

            <!-- Status & Actions -->
            <div class="text-right">
                <span class="inline-block px-3 py-1 text-sm font-medium rounded-full mb-4
                    @if($gamingSession->status === 'scheduled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @elseif($gamingSession->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($gamingSession->status === 'completed') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @endif">
                    {{ ucfirst($gamingSession->status) }}
                </span>

                <div class="space-y-2">
                    @if($gamingSession->host_user_id === Auth::id())
                        <!-- Host Actions -->
                        <div class="space-x-2">
                            @if($gamingSession->status === 'scheduled')
                                <a href="{{ route('gaming-sessions.edit', $gamingSession) }}" 
                                   class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md">
                                    Edit Session
                                </a>
                                <form action="{{ route('gaming-sessions.destroy', $gamingSession) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to cancel this session?')"
                                            class="inline-block px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md">
                                        Cancel Session
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <!-- Participant Actions -->
                        @if($userInvitation && $userInvitation->status === 'pending')
                            <div class="space-x-2">
                                <form action="{{ route('gaming-session-invitations.respond', $userInvitation) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" 
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md">
                                        Accept Invitation
                                    </button>
                                </form>
                                <form action="{{ route('gaming-session-invitations.respond', $userInvitation) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" 
                                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-md">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        @elseif($isParticipant)
                            @if($gamingSession->status === 'scheduled')
                                <form action="{{ route('gaming-sessions.leave', $gamingSession) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to leave this session?')"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md">
                                        Leave Session
                                    </button>
                                </form>
                            @endif
                        @elseif($canJoin)
                            <form action="{{ route('gaming-sessions.join', $gamingSession) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md">
                                    Join Session
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            @if($gamingSession->description)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Description</h3>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $gamingSession->description }}</p>
                </div>
            @endif

            <!-- Requirements -->
            @if($gamingSession->requirements)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Requirements</h3>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $gamingSession->requirements }}</p>
                </div>
            @endif

            <!-- Participants -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Participants ({{ $gamingSession->participants->count() }})</h3>
                
                @if($gamingSession->participants->count() > 0)
                    <div class="space-y-3">
                        @foreach($gamingSession->participants as $participant)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                            {{ substr($participant->user->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $participant->user->name }}
                                            @if($participant->user_id === $gamingSession->host_user_id)
                                                <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full ml-2">Host</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Joined {{ $participant->joined_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No participants yet.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Session Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Session Details</h3>
                
                <dl class="space-y-3">
                    @if($gamingSession->platform)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Platform</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $gamingSession->platform }}</dd>
                        </div>
                    @endif
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Privacy</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ ucfirst(str_replace('_', ' ', $gamingSession->privacy)) }}
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Max Participants</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $gamingSession->max_participants }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $gamingSession->created_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Invitations -->
            @if($gamingSession->host_user_id === Auth::id() && $gamingSession->invitations->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Invitations</h3>
                    
                    <div class="space-y-2">
                        @foreach($gamingSession->invitations as $invitation)
                            <div class="text-sm">
                                @if($invitation->invited_user_id)
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-900 dark:text-white">{{ $invitation->invitedUser->name }}</span>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if($invitation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @elseif($invitation->status === 'accepted') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @endif">
                                            {{ ucfirst($invitation->status) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-900 dark:text-white">{{ $invitation->invitedGroup->name }} (Group)</span>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if($invitation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @elseif($invitation->status === 'accepted') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @endif">
                                            {{ ucfirst($invitation->status) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-8">
        <a href="{{ route('gaming-sessions.index') }}" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Sessions
        </a>
    </div>
</div>
@endsection
