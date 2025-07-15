@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Gamertags for :name', ['name' => $user->name]) }}
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
                            {{ $user->name }}'s Public Gamertags
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            View all public gamertags for this user across different platforms
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('gamertags.index') }}" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            Back to All Gamertags
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($gamertags->count() > 0)
            <!-- Gamertags by Platform -->
            @foreach($gamertags as $platform => $platformGamertags)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <!-- Platform Icon/Badge -->
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($platform === 'steam') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($platform === 'xbox_live') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($platform === 'playstation_network') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                    @elseif($platform === 'nintendo_switch') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($platform === 'epic_games') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @elseif($platform === 'battle_net') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($platform === 'origin') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($platform === 'uplay') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($platform === 'gog') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                    @endif">
                                    {{ \App\Models\Gamertag::PLATFORMS[$platform] ?? ucfirst(str_replace('_', ' ', $platform)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Gamertags Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($platformGamertags as $gamertag)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                                {{ $gamertag->gamertag }}
                                            </h4>
                                            
                                            @if($gamertag->is_primary)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mb-2">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Primary
                                                </span>
                                            @endif

                                            @if($gamertag->bio)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                    {{ Str::limit($gamertag->bio, 100) }}
                                                </p>
                                            @endif

                                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                                Added {{ $gamertag->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No Public Gamertags</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ $user->name }} hasn't made any gamertags public yet.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('gamertags.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Browse All Gamertags
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
