@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Gaming Session</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Update your gaming session details</p>
    </div>

    <form action="{{ route('gaming-sessions.update', $gamingSession) }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Session Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Session Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Session Title *
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           value="{{ old('title', $gamingSession->title) }}"
                           placeholder="e.g., Epic Raid Night, Casual Battle Royale"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Game Selection -->
                <div class="md:col-span-2">
                    <label for="game_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Game *
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="game_search" 
                               placeholder="Search for a game or type manually..."
                               value="{{ old('game_name', $gamingSession->game_name) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white {{ $errors->has('game_name') ? 'border-red-500' : '' }}">
                        <input type="hidden" name="game_name" id="game_name" value="{{ old('game_name', $gamingSession->game_name) }}">
                        
                        <!-- Search Results -->
                        <div id="game_results" class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                            <!-- Results will be populated by JavaScript -->
                        </div>
                    </div>
                    @error('game_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        💡 Start typing to search IGDB database, or enter any game name manually
                    </p>
                    
                    <!-- Selected Game Display -->
                    <div id="selected_game" class="mt-3 {{ old('game_name', $gamingSession->game_name) ? '' : 'hidden' }}">
                        <div class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                            <img id="selected_game_cover" src="" alt="" class="w-12 h-16 object-cover rounded mr-3" style="display: none;">
                            <div>
                                <p id="selected_game_name" class="font-medium text-gray-900 dark:text-white">{{ old('game_name', $gamingSession->game_name) }}</p>
                                <p id="selected_game_platforms" class="text-sm text-gray-600 dark:text-gray-400">Custom Entry</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Platform -->
                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Platform
                    </label>
                    <select name="platform" 
                            id="platform" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Select Platform</option>
                        @foreach(\App\Models\Game::PLATFORMS as $value => $label)
                            <option value="{{ $value }}" {{ old('platform', $gamingSession->platform) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('platform')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Participants -->
                <div>
                    <label for="max_participants" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Max Participants *
                    </label>
                    <input type="number" 
                           name="max_participants" 
                           id="max_participants" 
                           value="{{ old('max_participants', $gamingSession->max_participants) }}"
                           min="2" 
                           max="50"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('max_participants') border-red-500 @enderror">
                    @error('max_participants')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Scheduled Date & Time -->
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date & Time *
                    </label>
                    <input type="datetime-local" 
                           name="scheduled_at" 
                           id="scheduled_at" 
                           value="{{ old('scheduled_at', $gamingSession->scheduled_at->format('Y-m-d\TH:i')) }}"
                           min="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('scheduled_at') border-red-500 @enderror">
                    @error('scheduled_at')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Privacy -->
                <div>
                    <label for="privacy" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Privacy *
                    </label>
                    <select name="privacy" 
                            id="privacy" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('privacy') border-red-500 @enderror">
                        <option value="friends_only" {{ old('privacy', $gamingSession->privacy) === 'friends_only' ? 'selected' : '' }}>
                            Friends Only
                        </option>
                        <option value="invite_only" {{ old('privacy', $gamingSession->privacy) === 'invite_only' ? 'selected' : '' }}>
                            Invite Only
                        </option>
                        <option value="public" {{ old('privacy', $gamingSession->privacy) === 'public' ? 'selected' : '' }}>
                            Public
                        </option>
                    </select>
                    @error('privacy')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              placeholder="Tell participants what to expect, any special requirements, etc."
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('description') border-red-500 @enderror">{{ old('description', $gamingSession->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Requirements -->
                <div class="md:col-span-2">
                    <label for="requirements" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Requirements
                    </label>
                    <textarea name="requirements" 
                              id="requirements" 
                              rows="2"
                              placeholder="e.g., Minimum level 50, microphone required, specific DLC needed"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('requirements') border-red-500 @enderror">{{ old('requirements', $gamingSession->requirements) }}</textarea>
                    @error('requirements')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('gaming-sessions.show', $gamingSession) }}" 
               class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                Update Session
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gameSearch = document.getElementById('game_search');
    const gameResults = document.getElementById('game_results');
    const gameNameInput = document.getElementById('game_name');
    const selectedGameDiv = document.getElementById('selected_game');
    let searchTimeout;

    // Set up CSRF token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    gameSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 2) {
            gameResults.classList.add('hidden');
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchGames(query);
        }, 300);
    });

    function searchGames(query) {
        fetch(`{{ route('gaming-sessions.search-games') }}?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401) {
                        throw new Error('Authentication required. Please refresh the page and try again.');
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(games => {
                gameResults.innerHTML = '';
                
                if (games.error) {
                    gameResults.innerHTML = `<div class="p-3 text-red-500">${games.error}</div>`;
                } else if (games.length === 0) {
                    gameResults.innerHTML = '<div class="p-3 text-gray-500 dark:text-gray-400">No games found. You can still type a game name manually.</div>';
                } else {
                    games.forEach(game => {
                        const gameItem = document.createElement('div');
                        gameItem.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                        gameItem.innerHTML = `
                            <div class="flex items-center">
                                ${game.cover_url ? `<img src="${game.cover_url}" alt="${game.name}" class="w-8 h-10 object-cover rounded mr-3">` : '<div class="w-8 h-10 bg-gray-300 dark:bg-gray-600 rounded mr-3 flex items-center justify-center text-xs text-gray-500">🎮</div>'}
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">${game.name}</div>
                                    ${game.platforms && game.platforms.length > 0 ? `<div class="text-sm text-gray-600 dark:text-gray-400">${game.platforms.join(', ')}</div>` : '<div class="text-sm text-gray-600 dark:text-gray-400">Multiple platforms</div>'}
                                </div>
                            </div>
                        `;
                        
                        gameItem.addEventListener('click', () => {
                            selectGame(game);
                        });
                        
                        gameResults.appendChild(gameItem);
                    });
                }
                
                gameResults.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error searching games:', error);
                gameResults.innerHTML = `<div class="p-3 text-yellow-600 dark:text-yellow-400">
                    <strong>IGDB search temporarily unavailable.</strong><br>
                    <small>You can still enter a game name manually below.</small>
                </div>`;
                gameResults.classList.remove('hidden');
            });
    }

    function selectGame(game) {
        gameSearch.value = game.name;
        gameNameInput.value = game.name;
        gameResults.classList.add('hidden');
        
        // Show selected game
        const coverImg = document.getElementById('selected_game_cover');
        if (game.cover_url) {
            coverImg.src = game.cover_url;
            coverImg.style.display = 'block';
        } else {
            coverImg.style.display = 'none';
        }
        
        document.getElementById('selected_game_name').textContent = game.name;
        document.getElementById('selected_game_platforms').textContent = (game.platforms && game.platforms.length > 0) ? game.platforms.join(', ') : 'Multiple platforms';
        selectedGameDiv.classList.remove('hidden');

        // Remove any validation errors
        clearGameValidationErrors();
    }

    function clearGameValidationErrors() {
        const gameSearchInput = document.getElementById('game_search');
        gameSearchInput.classList.remove('border-red-500');
        const errorElement = gameSearchInput.parentElement.querySelector('.text-red-500');
        if (errorElement) {
            errorElement.remove();
        }
    }

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!gameSearch.contains(e.target) && !gameResults.contains(e.target)) {
            gameResults.classList.add('hidden');
        }
    });

    // Allow manual game name entry
    gameSearch.addEventListener('blur', function() {
        const trimmedValue = this.value.trim();
        if (trimmedValue) {
            gameNameInput.value = trimmedValue;
            // Show manual entry in selected game area
            document.getElementById('selected_game_name').textContent = trimmedValue;
            document.getElementById('selected_game_platforms').textContent = 'Custom Entry';
            document.getElementById('selected_game_cover').style.display = 'none';
            selectedGameDiv.classList.remove('hidden');
            // Clear validation errors since we have a valid game name
            clearGameValidationErrors();
        }
    });

    // Sync the visible input with hidden input for form submission
    gameSearch.addEventListener('input', function() {
        const trimmedValue = this.value.trim();
        gameNameInput.value = trimmedValue;
        
        // If user is typing and has content, clear validation errors
        if (trimmedValue) {
            clearGameValidationErrors();
        }
        
        // Hide selected game display if input is cleared
        if (!trimmedValue) {
            selectedGameDiv.classList.add('hidden');
        }
    });

    // Initialize form if there's an old value
    if (gameNameInput.value) {
        gameSearch.value = gameNameInput.value;
        document.getElementById('selected_game_name').textContent = gameNameInput.value;
        document.getElementById('selected_game_platforms').textContent = 'Custom Entry';
        selectedGameDiv.classList.remove('hidden');
    }
});
</script>

@endsection
