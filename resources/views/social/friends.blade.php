@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('My Friends') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if($friends->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($friends as $connection)
                    @php
                        $friend = $connection->getOtherUser(Auth::id());
                    @endphp
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <!-- Friend Info -->
                            <div class="flex items-center mb-4">
                                <div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <span class="text-xl font-medium text-gray-700 dark:text-gray-300">
                                        {{ substr($friend->name, 0, 1) }}
                                    </span>
                                </div>                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $friend->name }}</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Connected {{ $connection->accepted_at ? $connection->accepted_at->diffForHumans() : 'recently' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Friend's Gamertags -->
                            @if($friend->gamertags->count() > 0)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gamertags:</h4>
                                    <div class="space-y-2">
                                        @foreach($friend->gamertags->take(3) as $gamertag)
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $gamertag->gamertag }}</p>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $gamertag->formatted_platform }}</p>
                                                </div>
                                                @if($gamertag->is_primary)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        Primary
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($friend->gamertags->count() > 3)
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                +{{ $friend->gamertags->count() - 3 }} more
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex space-x-2">
                                <form action="{{ route('user-connections.destroy', $connection) }}" method="POST" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500"
                                            onclick="return confirm('Are you sure you want to remove {{ $friend->name }} from your friends?')">
                                        Remove Friend
                                    </button>
                                </form>
                                <form action="{{ route('user-connections.block', $connection) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500"
                                            title="Block User"
                                            onclick="return confirm('Are you sure you want to block {{ $friend->name }}?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($friends->hasPages())
                <div class="mt-8">
                    {{ $friends->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No friends yet</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Start connecting with other gamers to build your gaming network!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('social.search') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search Users
                        </a>
                        <a href="{{ route('social.browse') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Browse All Users
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
