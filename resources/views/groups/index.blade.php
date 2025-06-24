@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Gaming Groups') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Discover Gaming Groups
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Find and join gaming communities for your favorite games
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('groups.my-groups') }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            My Groups
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
            </div>

            <!-- Search and Filters -->
            <div class="p-6">
                <form method="GET" action="{{ route('groups.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Groups</label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ request('search') }}" 
                                   placeholder="Search by group name or description..." 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="game" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Game</label>
                            <select name="game" id="game" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">All Games</option>
                                @foreach($games as $gameKey => $gameName)
                                    <option value="{{ $gameKey }}" {{ request('game') === $gameKey ? 'selected' : '' }}>
                                        {{ $gameName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                            <select name="platform" id="platform" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">All Platforms</option>
                                @foreach($platforms as $platformKey => $platformName)
                                    <option value="{{ $platformKey }}" {{ request('platform') === $platformKey ? 'selected' : '' }}>
                                        {{ $platformName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </button>
                        @if(request()->hasAny(['search', 'game', 'platform']))
                            <a href="{{ route('groups.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Groups Grid -->
        @if($groups->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($groups as $group)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <!-- Group Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        <a href="{{ route('groups.show', $group) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $group->name }}
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        by {{ $group->owner->name }}
                                    </p>
                                </div>
                                @if($group->avatar)
                                    <img src="{{ $group->avatar }}" alt="{{ $group->name }}" class="w-12 h-12 rounded-lg">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-lg font-bold text-white">
                                            {{ substr($group->name, 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Game and Platform Tags -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                @if($group->game)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        {{ $group->game_name }}
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                        {{ $group->platform_name }}
                                    </span>
                                @endif
                            </div>

                            <!-- Description -->
                            @if($group->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">
                                    {{ $group->description }}
                                </p>
                            @endif

                            <!-- Member Count and Actions -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    {{ $group->memberships_count }}/{{ $group->max_members }}
                                    @if($group->isFull())
                                        <span class="ml-1 text-red-500">Full</span>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    @if($group->hasMember(Auth::user()))
                                        <span class="px-3 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full">
                                            Member
                                        </span>
                                    @elseif($group->hasPendingInvitation(Auth::user()))
                                        <span class="px-3 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                            Invited
                                        </span>
                                    @elseif($group->is_public && !$group->isFull())
                                        <form action="{{ route('groups.join', $group) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded-full hover:bg-blue-700">
                                                Join
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <a href="{{ route('groups.show', $group) }}" 
                                       class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($groups->hasPages())
                <div class="mt-8">
                    {{ $groups->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        @if(request()->hasAny(['search', 'game', 'platform']))
                            No groups found
                        @else
                            No gaming groups yet
                        @endif
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        @if(request()->hasAny(['search', 'game', 'platform']))
                            Try adjusting your search criteria or create a new group.
                        @else
                            Get started by creating the first gaming group for your favorite game!
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('groups.create') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Your First Group
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
