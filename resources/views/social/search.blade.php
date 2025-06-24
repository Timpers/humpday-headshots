@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Search Users & Gamertags') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">        <!-- Search Form -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('social.search') }}" class="space-y-4" id="searchForm">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                            <input type="text" 
                                   name="query" 
                                   id="searchInput"
                                   value="{{ $query ?? '' }}" 
                                   placeholder="Search by name, email, or gamertag..." 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                   autocomplete="off">
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Type</label>
                            <select name="type" id="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="users" {{ ($type ?? '') === 'users' ? 'selected' : '' }}>Users</option>
                                <option value="gamertags" {{ ($type ?? '') === 'gamertags' ? 'selected' : '' }}>Gamertags</option>
                            </select>
                        </div>
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                            <select name="platform" id="platform" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">All Platforms</option>
                                @if(isset($platforms))
                                    @foreach($platforms as $platformKey => $platformName)
                                        <option value="{{ $platformKey }}" {{ ($platform ?? '') === $platformKey ? 'selected' : '' }}>
                                            {{ $platformName }}
                                        </option>
                                    @endforeach
                                @endif
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
                        @if($query || $platform)
                            <a href="{{ route('social.search') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <div id="searchResults">
            @if(isset($results))
                <!-- Users Results -->
                @if(isset($results['users']) && $results['users']->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Users ({{ $results['users']->count() }})</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($results['users'] as $user)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="flex items-center mb-3">
                                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                                <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                                    {{ substr($user['name'], 0, 1) }}
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $user['name'] }}</h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user['email'] }}</p>
                                            </div>
                                        </div>
                                        
                                        @if($user['gamertags']->count() > 0)
                                            <div class="mb-3">
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Gamertags:</p>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($user['gamertags'] as $gamertag)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                            {{ $gamertag->formatted_platform }}: {{ $gamertag->gamertag }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Connection Action -->
                                        @if($user['connection_status'] === null)
                                            <form action="{{ route('connections.store') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="recipient_id" value="{{ $user['id'] }}">
                                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                                    Send Connection Request
                                                </button>
                                            </form>
                                        @elseif($user['connection_status']['status'] === 'pending')
                                            @if($user['connection_status']['is_requester'])
                                                <span class="w-full inline-block text-center px-4 py-2 bg-yellow-100 text-yellow-800 text-sm rounded-md">
                                                    Request Sent
                                                </span>
                                            @else
                                                <div class="flex gap-2">
                                                    <form action="{{ route('connections.accept', $user['connection_status']['connection']) }}" method="POST" class="flex-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                                            Accept
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('connections.decline', $user['connection_status']['connection']) }}" method="POST" class="flex-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full px-3 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                                            Decline
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        @elseif($user['connection_status']['status'] === 'accepted')
                                            <span class="w-full inline-block text-center px-4 py-2 bg-green-100 text-green-800 text-sm rounded-md">
                                                Connected
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Gamertags Results -->
                @if(isset($results['gamertags']) && $results['gamertags']->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Gamertags ({{ $results['gamertags']->count() }})</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($results['gamertags'] as $gamertag)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $gamertag['gamertag'] }}</h4>
                                            @if($gamertag['is_primary'])
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                    Primary
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $gamertag['platform_formatted'] }}</p>
                                        
                                        @if($gamertag['display_name'])
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $gamertag['display_name'] }}</p>
                                        @endif
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Owner: {{ $gamertag['user']['name'] }}</p>

                                        <!-- Connection Action -->
                                        @if($gamertag['connection_status'] === null)
                                            <form action="{{ route('connections.store') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="recipient_id" value="{{ $gamertag['user']['id'] }}">
                                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                                    Connect with {{ $gamertag['user']['name'] }}
                                                </button>
                                            </form>
                                        @elseif($gamertag['connection_status']['status'] === 'pending')
                                            @if($gamertag['connection_status']['is_requester'])
                                                <span class="w-full inline-block text-center px-4 py-2 bg-yellow-100 text-yellow-800 text-sm rounded-md">
                                                    Request Sent
                                                </span>
                                            @else
                                                <div class="flex gap-2">
                                                    <form action="{{ route('connections.accept', $gamertag['connection_status']['connection']) }}" method="POST" class="flex-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                                            Accept
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('connections.decline', $gamertag['connection_status']['connection']) }}" method="POST" class="flex-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full px-3 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                                            Decline
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        @elseif($gamertag['connection_status']['status'] === 'accepted')
                                            <span class="w-full inline-block text-center px-4 py-2 bg-green-100 text-green-800 text-sm rounded-md">
                                                Connected
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- No Results -->
                @if((!isset($results['users']) || $results['users']->count() === 0) && 
                    (!isset($results['gamertags']) || $results['gamertags']->count() === 0))
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No results found</h3>
                            <p class="text-gray-600 dark:text-gray-400">Try adjusting your search terms or browse all users.</p>
                            <a href="{{ route('social.browse') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Browse All Users
                            </a>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const typeSelect = document.getElementById('type');
    const platformSelect = document.getElementById('platform');
    let searchTimeout;

    // Auto-search functionality
    function triggerSearch() {
        clearTimeout(searchTimeout);
        const query = searchInput.value.trim();
        const type = typeSelect.value;
        const platform = platformSelect.value;
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performAjaxSearch(query, type, platform);
            }, 500);
        } else if (query.length === 0) {
            // Show results for platform/type filters even without query
            if (type !== 'all' || platform !== '') {
                searchTimeout = setTimeout(() => {
                    performAjaxSearch('', type, platform);
                }, 300);
            } else {
                clearResults();
            }
        }
    }

    searchInput.addEventListener('input', triggerSearch);
    typeSelect.addEventListener('change', triggerSearch);
    platformSelect.addEventListener('change', triggerSearch);

    function performAjaxSearch(query, type, platform) {
        showLoading();
        
        fetch('{{ route("social.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                query: query,
                type: type,
                platform: platform
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                displaySearchResults(data.results, data.query, data.type, data.platform);
            } else {
                showError('Search failed. Please try again.');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Search error:', error);
            showError('Search failed. Please try again.');
        });
    }

    function displaySearchResults(results, query, type, platform) {
        const resultsContainer = document.getElementById('searchResults');
        let html = '';

        // Users Results
        if (results.users && results.users.length > 0) {
            html += `
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Users (${results.users.length})
                            ${platform ? ` on ${getPlatformName(platform)}` : ''}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            `;
            
            results.users.forEach(user => {
                html += createUserCard(user);
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        }

        // Gamertags Results
        if (results.gamertags && results.gamertags.length > 0) {
            html += `
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Gamertags (${results.gamertags.length})
                            ${platform ? ` on ${getPlatformName(platform)}` : ''}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            `;
            
            results.gamertags.forEach(gamertag => {
                html += createGamertagCard(gamertag);
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        }

        // No Results
        if ((!results.users || results.users.length === 0) && 
            (!results.gamertags || results.gamertags.length === 0)) {
            html = `
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No results found</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            ${query ? `No results for "${query}"` : 'No users found with the selected filters'}
                            ${platform ? ` on ${getPlatformName(platform)}` : ''}
                        </p>
                    </div>
                </div>
            `;
        }

        resultsContainer.innerHTML = html;
    }

    function createUserCard(user) {
        const connectionButton = getConnectionButton(user.connection_status, user.id);
        let gamertagsHtml = '';
        
        if (user.gamertags && user.gamertags.length > 0) {
            gamertagsHtml = `
                <div class="mb-3">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Gamertags:</p>
                    <div class="flex flex-wrap gap-1">
            `;
            user.gamertags.forEach(gamertag => {
                gamertagsHtml += `
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        ${gamertag.platform_formatted}: ${gamertag.gamertag}
                    </span>
                `;
            });
            gamertagsHtml += `
                    </div>
                </div>
            `;
        }

        return `
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                            ${user.name.charAt(0)}
                        </span>
                    </div>
                    <div class="ml-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">${user.name}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${user.email}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">${user.gamertags_count || 0} public gamertags</p>
                    </div>
                </div>
                ${gamertagsHtml}
                ${connectionButton}
            </div>
        `;
    }

    function createGamertagCard(gamertag) {
        const connectionButton = getConnectionButton(gamertag.connection_status, gamertag.user.id);
        
        return `
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-gray-900 dark:text-white">${gamertag.gamertag}</h4>
                    ${gamertag.is_primary ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Primary</span>' : ''}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">${gamertag.platform_formatted}</p>
                ${gamertag.display_name ? `<p class="text-sm text-gray-600 dark:text-gray-400 mb-2">${gamertag.display_name}</p>` : ''}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Owner: ${gamertag.user.name}</p>
                ${connectionButton}
            </div>
        `;
    }

    function getConnectionButton(connectionStatus, userId) {
        if (!connectionStatus) {
            return `
                <form action="{{ route('connections.store') }}" method="POST" class="inline">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="recipient_id" value="${userId}">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        Send Connection Request
                    </button>
                </form>
            `;
        }

        switch (connectionStatus.status) {
            case 'pending':
                if (connectionStatus.is_requester) {
                    return `<span class="w-full inline-block text-center px-4 py-2 bg-yellow-100 text-yellow-800 text-sm rounded-md">Request Sent</span>`;
                } else {
                    return `<span class="w-full inline-block text-center px-4 py-2 bg-blue-100 text-blue-800 text-sm rounded-md">Respond in Requests</span>`;
                }
            case 'accepted':
                return `<span class="w-full inline-block text-center px-4 py-2 bg-green-100 text-green-800 text-sm rounded-md">Connected</span>`;
            default:
                return `<span class="w-full inline-block text-center px-4 py-2 bg-gray-100 text-gray-800 text-sm rounded-md">Unavailable</span>`;
        }
    }

    function getPlatformName(platform) {
        const platforms = {
            'steam': 'Steam',
            'xbox_live': 'Xbox Live',
            'playstation_network': 'PlayStation Network',
            'nintendo_online': 'Nintendo Online',
            'battlenet': 'Battle.net'
        };
        return platforms[platform] || platform;
    }

    function showLoading() {
        const resultsContainer = document.getElementById('searchResults');
        resultsContainer.innerHTML = `
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Searching...</p>
                </div>
            </div>
        `;
    }

    function hideLoading() {
        // Loading will be replaced by results
    }

    function clearResults() {
        document.getElementById('searchResults').innerHTML = '';
    }

    function showError(message) {
        const resultsContainer = document.getElementById('searchResults');
        resultsContainer.innerHTML = `
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="w-12 h-12 mx-auto text-red-400 dark:text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Search Error</h3>
                    <p class="text-gray-600 dark:text-gray-400">${message}</p>
                </div>
            </div>
        `;
    }
});
</script>
@endsection
