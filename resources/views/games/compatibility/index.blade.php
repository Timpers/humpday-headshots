@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">🎮 Game Compatibility</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">Compare your game library with other users to find gaming partners</p>
                    </div>
                    <div class="hidden sm:block">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900 dark:text-blue-100">How it works</p>
                                    <p class="text-blue-700 dark:text-blue-300">Click on any user to see your game compatibility score and shared games!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($users as $user)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer group"
                     onclick="compareWith({{ $user->id }})">
                    <div class="p-6">
                        <!-- User Info -->
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $user->name }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $user->games->count() }} games owned
                                </p>
                            </div>
                        </div>

                        <!-- Games Preview -->
                        @if($user->games->count() > 0)
                            <div class="space-y-2">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Recent Games</p>
                                <div class="space-y-1">
                                    @foreach($user->games->take(3) as $game)
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $game->name }}</span>
                                        </div>
                                    @endforeach
                                    @if($user->games->count() > 3)
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                +{{ $user->games->count() - 3 }} more games
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No games yet</p>
                            </div>
                        @endif

                        <!-- Compare Button -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors group-hover:bg-blue-700">
                                Compare Games
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($users->count() === 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No other users found</h3>
                <p class="text-gray-600 dark:text-gray-400">There are no other users with game libraries to compare with yet.</p>
            </div>
        @endif

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="mt-8">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function compareWith(userId) {
    window.location.href = `/games/compatibility/compare/${userId}`;
}
</script>
@endpush
@endsection
