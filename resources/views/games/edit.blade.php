@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Game</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $game->name }} • {{ $game->formatted_platform }}
                        </p>
                    </div>
                    <a href="{{ route('games.show', $game) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>

                <form action="{{ route('games.update', $game) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <!-- Game Info Display (Read-only) -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                @if($game->cover_thumbnail)
                                    <img src="{{ $game->cover_thumbnail }}" alt="{{ $game->name }}" class="w-16 h-20 object-cover rounded">
                                @else
                                    <div class="w-16 h-20 bg-gray-300 dark:bg-gray-600 rounded flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $game->name }}</h3>
                                @if($game->release_date)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Released: {{ $game->release_date->format('F j, Y') }}</p>
                                @endif
                                @if($game->formatted_genres)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Genres: {{ $game->formatted_genres }}</p>
                                @endif
                                @if($game->rating)
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400">IGDB Rating: ★ {{ $game->rating }}/10</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Platform Selection -->
                    <div>
                        <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your Platform
                        </label>
                        <select 
                            id="platform" 
                            name="platform" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('platform') border-red-500 @enderror"
                        >
                            @foreach(\App\Models\Game::PLATFORMS as $key => $name)
                                <option value="{{ $key }}" {{ old('platform', $game->platform) === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('platform')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select 
                            id="status" 
                            name="status" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror"
                        >
                            @foreach(\App\Models\Game::STATUSES as $key => $name)
                                <option value="{{ $key }}" {{ old('status', $game->status) === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- User Rating -->
                        <div>
                            <label for="user_rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Your Rating (1-10)
                            </label>
                            <input 
                                type="number" 
                                id="user_rating" 
                                name="user_rating" 
                                value="{{ old('user_rating', $game->user_rating) }}"
                                min="1" 
                                max="10" 
                                step="0.1"
                                placeholder="Rate this game"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('user_rating') border-red-500 @enderror"
                            >
                            @error('user_rating')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Hours Played -->
                        <div>
                            <label for="hours_played" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hours Played
                            </label>
                            <input 
                                type="number" 
                                id="hours_played" 
                                name="hours_played" 
                                value="{{ old('hours_played', $game->hours_played) }}"
                                min="0"
                                placeholder="Hours played"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('hours_played') border-red-500 @enderror"
                            >
                            @error('hours_played')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date Purchased -->
                        <div>
                            <label for="date_purchased" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date Purchased
                            </label>
                            <input 
                                type="date" 
                                id="date_purchased" 
                                name="date_purchased" 
                                value="{{ old('date_purchased', $game->date_purchased?->format('Y-m-d')) }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('date_purchased') border-red-500 @enderror"
                            >
                            @error('date_purchased')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Price Paid -->
                        <div>
                            <label for="price_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Price Paid ($)
                            </label>
                            <input 
                                type="number" 
                                id="price_paid" 
                                name="price_paid" 
                                value="{{ old('price_paid', $game->price_paid) }}"
                                min="0" 
                                step="0.01"
                                placeholder="0.00"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('price_paid') border-red-500 @enderror"
                            >
                            @error('price_paid')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Personal Notes
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="4"
                            placeholder="Add any personal notes about this game..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('notes') border-red-500 @enderror"
                        >{{ old('notes', $game->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Checkboxes -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Game Properties</h3>
                        
                        <!-- Digital -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_digital" 
                                name="is_digital" 
                                value="1"
                                {{ old('is_digital', $game->is_digital) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                            >
                            <label for="is_digital" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Digital copy
                            </label>
                        </div>

                        <!-- Completed -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_completed" 
                                name="is_completed" 
                                value="1"
                                {{ old('is_completed', $game->is_completed) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                            >
                            <label for="is_completed" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Completed
                            </label>
                        </div>

                        <!-- Favorite -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_favorite" 
                                name="is_favorite" 
                                value="1"
                                {{ old('is_favorite', $game->is_favorite) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                            >
                            <label for="is_favorite" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Favorite game
                            </label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                        <form action="{{ route('games.destroy', $game) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this game from your library?')">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                            >
                                Remove from Library
                            </button>
                        </form>
                        
                        <div class="flex items-center space-x-4">
                            <a 
                                href="{{ route('games.show', $game) }}" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                            >
                                Update Game
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-update completion status when marking as completed
    const completedCheckbox = document.getElementById('is_completed');
    const statusSelect = document.getElementById('status');
    
    completedCheckbox.addEventListener('change', function() {
        if (this.checked && statusSelect.value !== 'completed') {
            if (confirm('Would you like to set the status to "Completed" as well?')) {
                statusSelect.value = 'completed';
            }
        }
    });
    
    // Auto-check completed when status is set to completed
    statusSelect.addEventListener('change', function() {
        if (this.value === 'completed') {
            completedCheckbox.checked = true;
        }
    });
});
</script>
@endsection
