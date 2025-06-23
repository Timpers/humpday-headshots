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

        <!-- Welcome Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Welcome back, {{ $user->name }}! 🎮</h1>
                        <p class="text-gray-600 dark:text-gray-400">Manage your gaming profiles and connect with other gamers.</p>
                    </div>
                    <div class="hidden sm:block">
                        <div class="flex items-center space-x-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $gamertags->count() }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Gamertags</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $platformStats->count() }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Platforms</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Gamertags Section -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Your Gamertags</h2>
                            <a 
                                href="{{ route('gamertags.create') }}" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Gamertag
                            </a>
                        </div>

                        @if($gamertags->count() > 0)
                            <div class="space-y-4">
                                @foreach($gamertags->groupBy('platform') as $platform => $platformGamertags)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full mr-2
                                                {{ $platform === 'steam' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' : '' }}
                                                {{ $platform === 'xbox_live' ? 'bg-green-100 text-green-800 dark:bg-green-600 dark:text-green-200' : '' }}
                                                {{ $platform === 'playstation_network' ? 'bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-blue-200' : '' }}
                                                {{ $platform === 'nintendo_online' ? 'bg-red-100 text-red-800 dark:bg-red-600 dark:text-red-200' : '' }}
                                                {{ $platform === 'battlenet' ? 'bg-purple-100 text-purple-800 dark:bg-purple-600 dark:text-purple-200' : '' }}">
                                                {{ \App\Models\Gamertag::PLATFORMS[$platform] }}
                                            </span>
                                        </h3>
                                        
                                        <div class="space-y-2">
                                            @foreach($platformGamertags as $gamertag)
                                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded p-3">
                                                    <div class="flex items-center space-x-3">
                                                        <span class="font-mono text-sm text-gray-900 dark:text-gray-100">
                                                            {{ $gamertag->gamertag }}
                                                        </span>
                                                        @if($gamertag->display_name)
                                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                                ({{ $gamertag->display_name }})
                                                            </span>
                                                        @endif
                                                        @if($gamertag->is_primary)
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-200 rounded">
                                                                Primary
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="flex items-center space-x-2">
                                                        @if($gamertag->is_public)
                                                            <span class="text-green-500 text-xs">Public</span>
                                                        @else
                                                            <span class="text-red-500 text-xs">Private</span>
                                                        @endif
                                                        
                                                        @if($gamertag->profile_url)
                                                            <a href="{{ $gamertag->profile_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                                </svg>
                                                            </a>
                                                        @endif
                                                          <a href="{{ route('gamertags.edit', $gamertag) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Edit gamertag">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        <form action="{{ route('gamertags.destroy', $gamertag) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this gamertag?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-400 hover:text-red-600" title="Delete gamertag">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No gamertags</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first gamertag.</p>                                <div class="mt-6">
                                    <a 
                                        href="{{ route('gamertags.create') }}"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add your first gamertag
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Stats -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                        <div class="space-y-3">                            <a 
                                href="{{ route('gamertags.create') }}"
                                class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors block"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-indigo-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Add Gamertag</span>
                                </div>                            </a>
                            
                            <a 
                                href="{{ route('games.index') }}"
                                class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors block"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3M5 9h14M5 15h14"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Game Library</span>
                                </div>
                            </a>
                            
                            <button class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 119.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Find Friends</span>
                                </div>
                            </button>
                            
                            <button class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Settings</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Platform Coverage -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Platform Coverage</h3>
                        <div class="space-y-3">
                            @foreach(\App\Models\Gamertag::PLATFORMS as $key => $name)
                                @php $hasGamertag = $platformStats->has($key); @endphp
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $name }}</span>
                                    @if($hasGamertag)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-600 dark:text-green-200 rounded">
                                            {{ $platformStats[$key] }} gamertag{{ $platformStats[$key] > 1 ? 's' : '' }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Not connected</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
