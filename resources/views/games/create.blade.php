@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Add Game to Library</h1>
                    <a href="{{ route('games.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>

                <!-- Game Search -->
                <div class="mb-8">
                    <label for="game-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Search for a Game
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="game-search" 
                            placeholder="Type a game name to search..."
                            class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Search the IGDB database for accurate game information
                    </p>
                </div>

                <!-- Loading Indicator -->
                <div id="loading" class="hidden text-center py-4">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Searching...
                    </div>
                </div>

                <!-- Search Results -->
                <div id="search-results" class="mb-8 hidden">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Search Results</h3>
                    <div id="games-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Results will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Selected Game Form -->
                <div id="game-form" class="hidden">
                    <form action="{{ route('games.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- Hidden fields for IGDB data -->
                        <input type="hidden" id="igdb_id" name="igdb_id">
                        <input type="hidden" id="igdb_data" name="igdb_data">
                        
                        <!-- Selected Game Display -->
                        <div id="selected-game" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <!-- Will be populated by JavaScript -->
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
                                <option value="">Select your platform</option>
                                @foreach(\App\Models\Game::PLATFORMS as $key => $name)
                                    <option value="{{ $key }}" {{ old('platform') === $key ? 'selected' : '' }}>
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
                                    <option value="{{ $key }}" {{ old('status', 'owned') === $key ? 'selected' : '' }}>
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
                                    value="{{ old('user_rating') }}"
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
                                    value="{{ old('hours_played') }}"
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
                                    value="{{ old('date_purchased') }}"
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
                                    value="{{ old('price_paid') }}"
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
                                rows="3"
                                placeholder="Add any personal notes about this game..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('notes') border-red-500 @enderror"
                            >{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-4">
                            <!-- Digital -->
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_digital" 
                                    name="is_digital" 
                                    value="1"
                                    {{ old('is_digital', true) ? 'checked' : '' }}
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
                                    {{ old('is_completed') ? 'checked' : '' }}
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
                                    {{ old('is_favorite') ? 'checked' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                                >
                                <label for="is_favorite" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Favorite game
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a 
                                href="{{ route('games.index') }}" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                            >
                                Add to Library
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('game-search');
    const loading = document.getElementById('loading');
    const searchResults = document.getElementById('search-results');
    const gamesGrid = document.getElementById('games-grid');
    const gameForm = document.getElementById('game-form');
    const selectedGameDiv = document.getElementById('selected-game');
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            gameForm.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchGames(query);
        }, 500);
    });
    
    function searchGames(query) {
        loading.classList.remove('hidden');
        searchResults.classList.add('hidden');
        gameForm.classList.add('hidden');
        
        fetch('{{ route("games.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ search_query: query })
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            
            if (data.success && data.games.length > 0) {
                displaySearchResults(data.games);
            } else {
                showNoResults();
            }
        })
        .catch(error => {
            loading.classList.add('hidden');
            console.error('Search error:', error);
            showError('Failed to search games. Please try again.');
        });
    }
    
    function displaySearchResults(games) {
        gamesGrid.innerHTML = '';
        
        games.forEach(game => {
            const gameCard = createGameCard(game);
            gamesGrid.appendChild(gameCard);
        });
        
        searchResults.classList.remove('hidden');
    }
    
    function createGameCard(game) {
        const card = document.createElement('div');
        card.className = 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors';
        
        const coverUrl = game.cover ? `https:${game.cover.url.replace('t_thumb', 't_cover_small')}` : '';
        
        card.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    ${coverUrl ? 
                        `<img src="${coverUrl}" alt="${game.name}" class="w-16 h-20 object-cover rounded">` :
                        `<div class="w-16 h-20 bg-gray-300 dark:bg-gray-600 rounded flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2"></path>
                            </svg>
                        </div>`
                    }
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2">${game.name}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${game.first_release_date || 'Release date unknown'}</p>
                    ${game.genres.length > 0 ? 
                        `<p class="text-xs text-gray-600 dark:text-gray-300 mt-1">${game.genres.map(g => g.name).join(', ')}</p>` : 
                        ''
                    }
                    ${game.rating ? 
                        `<p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">★ ${game.rating}/10</p>` : 
                        ''
                    }
                </div>
            </div>
        `;
        
        card.addEventListener('click', () => selectGame(game));
        
        return card;
    }
    
    function selectGame(game) {
        // Fill hidden fields
        document.getElementById('igdb_id').value = game.id;
        document.getElementById('igdb_data').value = JSON.stringify(game);
        
        // Update game form name
        const nameInput = document.querySelector('input[name="name"]');
        if (!nameInput) {
            const hiddenName = document.createElement('input');
            hiddenName.type = 'hidden';
            hiddenName.name = 'name';
            hiddenName.value = game.name;
            gameForm.querySelector('form').appendChild(hiddenName);
        } else {
            nameInput.value = game.name;
        }
        
        // Display selected game
        const coverUrl = game.cover ? `https:${game.cover.url.replace('t_thumb', 't_cover_small')}` : '';
        
        selectedGameDiv.innerHTML = `
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    ${coverUrl ? 
                        `<img src="${coverUrl}" alt="${game.name}" class="w-20 h-24 object-cover rounded">` :
                        `<div class="w-20 h-24 bg-gray-300 dark:bg-gray-600 rounded flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2"></path>
                            </svg>
                        </div>`
                    }
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">${game.name}</h3>
                    ${game.first_release_date ? `<p class="text-sm text-gray-600 dark:text-gray-400">Released: ${game.first_release_date}</p>` : ''}
                    ${game.genres.length > 0 ? `<p class="text-sm text-gray-600 dark:text-gray-400">Genres: ${game.genres.map(g => g.name).join(', ')}</p>` : ''}
                    ${game.rating ? `<p class="text-sm text-yellow-600 dark:text-yellow-400">IGDB Rating: ★ ${game.rating}/10</p>` : ''}
                    ${game.summary ? `<p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-3">${game.summary}</p>` : ''}
                </div>
            </div>
        `;
        
        searchResults.classList.add('hidden');
        gameForm.classList.remove('hidden');
    }
    
    function showNoResults() {
        gamesGrid.innerHTML = `
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No games found. Try a different search term.</p>
            </div>
        `;
        searchResults.classList.remove('hidden');
    }
    
    function showError(message) {
        gamesGrid.innerHTML = `
            <div class="col-span-full text-center py-8">
                <p class="text-red-600 dark:text-red-400">${message}</p>
            </div>
        `;
        searchResults.classList.remove('hidden');
    }
});
</script>
@endsection
