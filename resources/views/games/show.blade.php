@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('games.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $game->name }}</h1>
                        @if($game->is_favorite)
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        <a 
                            href="{{ route('games.edit', $game) }}" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Game
                        </a>
                        <form action="{{ route('games.destroy', $game) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this game from your library?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Game Cover & Basic Info -->
                    <div class="lg:col-span-1">
                        <div class="aspect-w-3 aspect-h-4 mb-4">
                            @if($game->cover_url)
                                <img src="{{ $game->cover_url }}" alt="{{ $game->name }}" class="w-full h-80 object-cover rounded-lg shadow-md">
                            @else
                                <div class="w-full h-80 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</span>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    {{ $game->status === 'owned' ? 'bg-green-100 text-green-800 dark:bg-green-600 dark:text-green-200' : '' }}
                                    {{ $game->status === 'wishlist' ? 'bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-200' : '' }}
                                    {{ $game->status === 'playing' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-200' : '' }}
                                    {{ $game->status === 'completed' ? 'bg-purple-100 text-purple-800 dark:bg-purple-600 dark:text-purple-200' : '' }}">
                                    {{ $game->formatted_status }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $game->formatted_platform }}</span>
                            </div>

                            @if($game->user_rating)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Your Rating</span>
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

                            @if($game->rating)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">IGDB Rating</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $game->rating }}/10</span>
                                </div>
                            @endif

                            @if($game->hours_played)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Hours Played</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ number_format($game->hours_played) }}h</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Format</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $game->is_digital ? 'Digital' : 'Physical' }}</span>
                            </div>

                            @if($game->is_completed)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</span>
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Game Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Game Summary -->
                        @if($game->summary)
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">About</h2>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $game->summary }}</p>
                            </div>
                        @endif

                        <!-- Game Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($game->release_date)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Release Date</h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $game->release_date->format('F j, Y') }}</p>
                                </div>
                            @endif

                            @if($game->formatted_genres)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Genres</h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $game->formatted_genres }}</p>
                                </div>
                            @endif

                            @if($game->date_purchased)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Date Purchased</h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $game->date_purchased->format('F j, Y') }}</p>
                                </div>
                            @endif

                            @if($game->price_paid)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Price Paid</h3>
                                    <p class="text-gray-700 dark:text-gray-300">${{ number_format($game->price_paid, 2) }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Personal Notes -->
                        @if($game->notes)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Your Notes</h3>
                                <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                                    <p class="text-gray-700 dark:text-gray-300">{{ $game->notes }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Available Platforms (from IGDB) -->
                        @if($game->platforms && count($game->platforms) > 0)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Available Platforms</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($game->platforms as $platform)
                                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200 rounded-full">
                                            {{ $platform['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Game Statistics -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Game Statistics</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ $game->hours_played ?? 0 }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Hours</div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ $game->user_rating ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Your Rating</div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                        {{ $game->is_completed ? '100%' : '0%' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Complete</div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                        {{ $game->price_paid ? '$' . number_format($game->price_paid, 0) : 'Free' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Paid</div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <a 
                                href="{{ route('games.edit', $game) }}" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Details
                            </a>

                            @if($game->status !== 'completed')
                                <form action="{{ route('games.update', $game) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="platform" value="{{ $game->platform }}">
                                    <input type="hidden" name="status" value="{{ $game->status }}">
                                    <input type="hidden" name="is_completed" value="1">
                                    <input type="hidden" name="is_digital" value="{{ $game->is_digital ? '1' : '0' }}">
                                    <input type="hidden" name="is_favorite" value="{{ $game->is_favorite ? '1' : '0' }}">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Mark Complete
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('games.update', $game) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="platform" value="{{ $game->platform }}">
                                <input type="hidden" name="status" value="{{ $game->status }}">
                                <input type="hidden" name="is_completed" value="{{ $game->is_completed ? '1' : '0' }}">
                                <input type="hidden" name="is_digital" value="{{ $game->is_digital ? '1' : '0' }}">
                                <input type="hidden" name="is_favorite" value="{{ $game->is_favorite ? '0' : '1' }}">
                                <button type="submit" class="{{ $game->is_favorite ? 'bg-gray-600 hover:bg-gray-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    {{ $game->is_favorite ? 'Remove from Favorites' : 'Add to Favorites' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
