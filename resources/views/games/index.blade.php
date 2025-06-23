@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">🎮 Game Library</h1>
                        <p class="text-gray-600 dark:text-gray-400">Manage your game collection across all platforms</p>
                    </div>
                    <a 
                        href="{{ route('games.create') }}" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Game
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filters</h3>
                        
                        <!-- Status Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select id="status-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option value="">All Games</option>
                                @foreach(\App\Models\Game::STATUSES as $key => $name)
                                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Platform Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                            <select id="platform-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option value="">All Platforms</option>
                                @foreach(\App\Models\Game::PLATFORMS as $key => $name)
                                    <option value="{{ $key }}" {{ request('platform') === $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Library Stats</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Games</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Owned</span>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $stats['owned'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Wishlist</span>
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $stats['wishlist'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Playing</span>
                                <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">{{ $stats['playing'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Completed</span>
                                <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ $stats['completed'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Games Grid -->
            <div class="lg:col-span-3">
                @if($games->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
                        @foreach($games as $game)
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                                <!-- Game Cover -->
                                <div class="aspect-w-3 aspect-h-4 bg-gray-100 dark:bg-gray-700">
                                    @if($game->cover_url)
                                        <img src="{{ $game->cover_url }}" alt="{{ $game->name }}" class="w-full h-48 object-cover">
                                    @else
                                        <div class="w-full h-48 flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="p-4">
                                    <!-- Game Title & Status -->
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                                            {{ $game->name }}
                                        </h3>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ml-2 flex-shrink-0
                                            {{ $game->status === 'owned' ? 'bg-green-100 text-green-800 dark:bg-green-600 dark:text-green-200' : '' }}
                                            {{ $game->status === 'wishlist' ? 'bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-200' : '' }}
                                            {{ $game->status === 'playing' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-200' : '' }}
                                            {{ $game->status === 'completed' ? 'bg-purple-100 text-purple-800 dark:bg-purple-600 dark:text-purple-200' : '' }}">
                                            {{ $game->formatted_status }}
                                        </span>
                                    </div>

                                    <!-- Platform & Favorite -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $game->formatted_platform }}
                                        </span>
                                        @if($game->is_favorite)
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endif
                                    </div>

                                    <!-- Rating -->
                                    @if($game->user_rating)
                                        <div class="flex items-center mb-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Your Rating:</span>
                                            <div class="flex items-center">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-4 h-4 {{ $i <= $game->user_rating / 2 ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                    </svg>
                                                @endfor
                                                <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">{{ $game->user_rating }}/10</span>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                        <a href="{{ route('games.show', $game) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                            View Details
                                        </a>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('games.edit', $game) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Edit game">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            
                                            <form action="{{ route('games.destroy', $game) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this game from your library?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600" title="Remove game">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    {{ $games->links() }}
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No games in your library</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Start building your game library by adding your first game.</p>
                        <a 
                            href="{{ route('games.create') }}"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Your First Game
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    const platformFilter = document.getElementById('platform-filter');
    
    function updateFilters() {
        const url = new URL(window.location.href);
        const status = statusFilter.value;
        const platform = platformFilter.value;
        
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        if (platform) {
            url.searchParams.set('platform', platform);
        } else {
            url.searchParams.delete('platform');
        }
        
        window.location.href = url.toString();
    }
    
    statusFilter.addEventListener('change', updateFilters);
    platformFilter.addEventListener('change', updateFilters);
});
</script>
@endsection
