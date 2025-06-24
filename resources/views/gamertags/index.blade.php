@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('All Gamertags') }}
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
                            Public Gamertags Directory
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Discover gamers across different platforms. Total: {{ $gamertags->total() }} public gamertags
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('social.search') }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </a>
                        <a href="{{ route('gamertags.create') }}" 
                           class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Gamertag
                        </a>
                    </div>
                </div>
            </div>

            <!-- Platform Filter Buttons -->
            <div class="p-6">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('gamertags.index') }}" 
                       class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors {{ !request('platform') ? 'ring-2 ring-blue-500' : '' }}">
                        All Platforms
                    </a>
                    @foreach(\App\Models\Gamertag::PLATFORMS as $platform => $platformName)
                        <a href="{{ route('gamertags.platform', $platform) }}" 
                           class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors {{ request()->route('platform') === $platform ? 'ring-2 ring-blue-500' : '' }}">
                            {{ $platformName }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Gamertags List -->
        @if($gamertags->count() > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($gamertags as $gamertag)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <!-- Platform Badge -->
                                    <div class="flex-shrink-0">
                                        @php
                                            $platformColors = [
                                                'steam' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'xbox_live' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'playstation_network' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                                                'nintendo_online' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'battlenet' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                            ];
                                            $colorClass = $platformColors[$gamertag->platform] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                            {{ $gamertag->platform_name }}
                                        </span>
                                    </div>

                                    <!-- Gamertag Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                {{ $gamertag->gamertag }}
                                            </h4>
                                            @if($gamertag->is_primary)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                    Primary
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($gamertag->display_name && $gamertag->display_name !== $gamertag->gamertag)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Display Name: {{ $gamertag->display_name }}
                                            </p>
                                        @endif
                                        
                                        <div class="flex items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{ $gamertag->user->name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2">
                                    @if($gamertag->profile_url)
                                        <a href="{{ $gamertag->profile_url }}" 
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            View Profile
                                        </a>
                                    @endif
                                    
                                    @if(Auth::id() !== $gamertag->user_id)
                                        <a href="{{ route('social.search', ['query' => $gamertag->user->name]) }}" 
                                           class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                            Connect
                                        </a>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 text-sm bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-md">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Your Gamertag
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Pagination -->
            @if($gamertags->hasPages())
                <div class="mt-6">
                    {{ $gamertags->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        No gamertags found
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No public gamertags are currently available. Be the first to add one!
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('gamertags.create') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Your First Gamertag
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Add any JavaScript for interactive features here
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth hover effects are handled by CSS
        console.log('Gamertags index loaded successfully');
    });
</script>
@endpush
@endsection
