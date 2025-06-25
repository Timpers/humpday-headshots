@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('games.compatibility.index') }}" 
                               class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Game Compatibility</h1>
                                <p class="text-gray-600 dark:text-gray-400">Comparing your library with {{ $user->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compatibility Score -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-32 h-32 rounded-full mb-4" 
                         style="background: conic-gradient(from 0deg, #3b82f6 0%, #3b82f6 {{ $comparison['compatibility_score'] }}%, #e5e7eb {{ $comparison['compatibility_score'] }}%, #e5e7eb 100%);">
                        <div class="w-24 h-24 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $comparison['compatibility_score'] }}%
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    compatibility
                                </div>
                            </div>
                        </div>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        {{ $comparison['compatibility_rating'] }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        You have {{ $comparison['total_shared_games'] }} games in common out of {{ $comparison['total_user1_games'] + $comparison['total_user2_games'] }} total games
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Library Overview -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Library Overview</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Your Games</span>
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $comparison['total_user1_games'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $user->name }}'s Games</span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $comparison['total_user2_games'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Shared Games</span>
                            <span class="text-sm font-semibold text-purple-600 dark:text-purple-400">{{ $comparison['total_shared_games'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Compatibility -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Platform Compatibility</h3>
                    <div class="space-y-3">
                        @forelse($comparison['platform_compatibility'] as $platform)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $platform['platform'] }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs px-2 py-1 rounded {{ $platform['user1_count'] > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                        You: {{ $platform['user1_count'] }}
                                    </span>
                                    <span class="text-xs px-2 py-1 rounded {{ $platform['user2_count'] > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $user->name }}: {{ $platform['user2_count'] }}
                                    </span>
                                    @if($platform['shared'])
                                        <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No platform data available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Shared Games -->
        @if($comparison['shared_games']->count() > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        🎮 Shared Games ({{ $comparison['shared_games']->count() }})
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($comparison['shared_games'] as $game)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start space-x-3">
                                    @if($game->cover && isset($game->cover['url']))
                                        <img src="https:{{ str_replace('t_thumb', 't_cover_small', $game->cover['url']) }}" 
                                             alt="{{ $game->name }}" 
                                             class="w-12 h-16 object-cover rounded">
                                    @else
                                        <div class="w-12 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $game->name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Models\Game::PLATFORMS[$game->platform] ?? $game->platform }}</p>
                                        @if($game->is_favorite)
                                            <div class="flex items-center mt-1">
                                                <svg class="w-3 h-3 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                <span class="text-xs text-yellow-600 dark:text-yellow-400">Favorite</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Game Recommendations -->
        @if(count($comparison['recommendations']) > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        ⭐ Recommended Games from {{ $user->name }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        These are highly-rated games that {{ $user->name }} owns but you don't have yet.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($comparison['recommendations'] as $game)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start space-x-3">
                                    @if($game['cover'] && isset($game['cover']['url']))
                                        <img src="https:{{ str_replace('t_thumb', 't_cover_small', $game['cover']['url']) }}" 
                                             alt="{{ $game['name'] }}" 
                                             class="w-12 h-16 object-cover rounded">
                                    @else
                                        <div class="w-12 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $game['name'] }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $game['platform_name'] }}</p>
                                        <div class="flex items-center mt-1 space-x-2">
                                            @if($game['user_rating'])
                                                <div class="flex items-center">
                                                    <svg class="w-3 h-3 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $game['user_rating'] }}/10</span>
                                                </div>
                                            @endif
                                            @if($game['is_favorite'])
                                                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded">
                                                    Favorite
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Genre Compatibility -->
        @if(count($comparison['genre_compatibility']) > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Genre Preferences</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($comparison['genre_compatibility'] as $genre)
                            <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg {{ $genre['shared'] ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800' : '' }}">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $genre['genre'] }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        You: {{ $genre['user1_count'] }}
                                    </span>
                                    <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ $user->name }}: {{ $genre['user2_count'] }}
                                    </span>
                                    @if($genre['shared'])
                                        <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-4">
            <a href="{{ route('games.compatibility.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors">
                Compare with Others
            </a>
            <a href="{{ route('social.index') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors">
                Connect with {{ $user->name }}
            </a>
        </div>
    </div>
</div>
@endsection
