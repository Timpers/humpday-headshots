@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                🎮 Gamertag Database Test
            </h1>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Users and their Gamertags -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Users & Their Gamertags
                    </h2>
                    
                    @foreach($users as $user)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-2">
                                {{ $user->name }}
                            </h3>
                            
                            @if($user->gamertags->count() > 0)
                                <div class="space-y-2">
                                    @foreach($user->gamertags as $gamertag)
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                                    {{ $gamertag->platform === 'steam' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' : '' }}
                                                    {{ $gamertag->platform === 'xbox_live' ? 'bg-green-100 text-green-800 dark:bg-green-600 dark:text-green-200' : '' }}
                                                    {{ $gamertag->platform === 'playstation_network' ? 'bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-200' : '' }}
                                                    {{ $gamertag->platform === 'nintendo_online' ? 'bg-red-100 text-red-800 dark:bg-red-600 dark:text-red-200' : '' }}
                                                    {{ $gamertag->platform === 'battlenet' ? 'bg-purple-100 text-purple-800 dark:bg-purple-600 dark:text-purple-200' : '' }}">
                                                    {{ $gamertag->platform_name }}
                                                </span>
                                                <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                                    {{ $gamertag->gamertag }}
                                                </span>
                                                @if($gamertag->is_primary)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-200 rounded">
                                                        Primary
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex items-center space-x-1">
                                                @if($gamertag->is_public)
                                                    <span class="text-green-500 text-xs">Public</span>
                                                @else
                                                    <span class="text-red-500 text-xs">Private</span>
                                                @endif
                                                
                                                @if($gamertag->profile_url)
                                                    <a href="{{ $gamertag->profile_url }}" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs">
                                                        🔗
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No gamertags</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Platform Statistics -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Platform Statistics
                    </h2>
                    
                    <div class="space-y-3">
                        @foreach($platformStats as $platform => $count)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ \App\Models\Gamertag::PLATFORMS[$platform] }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-200 rounded">
                                        {{ $count }} gamertags
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">Total Stats</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <div>Total Users: {{ $totalUsers }}</div>
                            <div>Total Gamertags: {{ $totalGamertags }}</div>
                            <div>Public Gamertags: {{ $publicGamertags }}</div>
                            <div>Primary Gamertags: {{ $primaryGamertags }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Relationships -->
            <div class="mt-8 bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                <h3 class="font-medium text-blue-900 dark:text-blue-200 mb-2">
                    🧪 Test Relationship Methods
                </h3>
                <div class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                    @if($users->isNotEmpty())
                        @php $firstUser = $users->first(); @endphp
                        <div><strong>{{ $firstUser->name }}</strong>'s Steam gamertag: 
                            {{ $firstUser->getGamertagForPlatform('steam')?->gamertag ?? 'None' }}
                        </div>
                        <div><strong>{{ $firstUser->name }}</strong>'s primary Xbox gamertag: 
                            {{ $firstUser->getPrimaryGamertagForPlatform('xbox_live')?->gamertag ?? 'None' }}
                        </div>
                        <div><strong>{{ $firstUser->name }}</strong> has {{ $firstUser->publicGamertags->count() }} public gamertags</div>
                    @endif
                </div>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Database models and relationships working perfectly! ✅
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
