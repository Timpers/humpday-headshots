@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gaming Sessions</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Schedule and join gaming sessions with friends and groups</p>
        </div>
        <a href="{{ route('gaming-sessions.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Session
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('gaming-sessions.index') }}" class="space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
            <!-- Type Filter -->
            <div class="flex-1">
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">View</label>
                <select name="type" id="type" 
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All My Sessions</option>
                    <option value="hosting" {{ $type === 'hosting' ? 'selected' : '' }}>Hosting</option>
                    <option value="participating" {{ $type === 'participating' ? 'selected' : '' }}>Participating</option>
                    <option value="invited" {{ $type === 'invited' ? 'selected' : '' }}>Invited</option>
                    <option value="public" {{ $type === 'public' ? 'selected' : '' }}>Public Sessions</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="flex-1">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" id="status" 
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Upcoming</option>
                    <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Search -->
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       value="{{ $search }}"
                       placeholder="Search by title or game..."
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <!-- Filter Button -->
            <div class="md:pt-6">
                <button type="submit" 
                        class="w-full md:w-auto bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Sessions Grid -->
    @if($sessions->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($sessions as $session)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <!-- Session Header -->
                    <div class="p-6">
                        <!-- Game & Title -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ $session->title }}
                                </h3>
                                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $session->game_name }}
                                </p>
                                @if($session->platform)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $session->platform }}
                                    </p>
                                @endif
                            </div>
                            
                            <!-- Status Badge -->
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($session->status === 'scheduled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($session->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($session->status === 'completed') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($session->status) }}
                            </span>
                        </div>

                        <!-- Session Info -->
                        <div class="space-y-2 mb-4">
                            <!-- Date & Time -->
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $session->scheduled_at->format('M j, Y') }} at {{ $session->scheduled_at->format('g:i A') }}
                            </div>

                            <!-- Host -->
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Hosted by {{ $session->host->name }}
                            </div>

                            <!-- Participants -->
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                {{ $session->participants->count() }}/{{ $session->max_participants }} participants
                            </div>

                            <!-- Privacy -->
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($session->privacy === 'public')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    @endif
                                </svg>
                                {{ ucfirst(str_replace('_', ' ', $session->privacy)) }}
                            </div>
                        </div>

                        <!-- Description -->
                        @if($session->description)
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-2">
                                {{ $session->description }}
                            </p>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('gaming-sessions.show', $session) }}" 
                               class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium text-sm">
                                View Details
                            </a>
                            
                            @if($session->isSoon())
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 text-xs font-medium rounded-full">
                                    Starting Soon
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $sessions->appends(request()->query())->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No gaming sessions found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if($type === 'public')
                    No public gaming sessions available at the moment.
                @else
                    Get started by creating your first gaming session.
                @endif
            </p>
            <div class="mt-6">
                <a href="{{ route('gaming-sessions.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Gaming Session
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
